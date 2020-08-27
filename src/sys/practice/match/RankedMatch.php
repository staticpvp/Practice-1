<?php


namespace sys\practice\match;


use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;

class RankedMatch extends Match {

	public function kill(): void {
		if (count($this->getPlayers()) <= 1 && $this->getWinner() instanceof PracticePlayer) {
			$loser = $this->getOtherPlayer($this->getWinner());
			$this->calculateNewElo($this->getWinner(), $loser);
		}
		parent::kill();
	}

	/**
	 * @return bool
	 */
	public function isRanked(): bool {
		return true;
	}

	/**
	 * @param PracticePlayer $winner
	 * @param PracticePlayer $loser
	 */
	public function calculateNewElo(PracticePlayer $winner, PracticePlayer $loser): void {
		$winnerElo = $winner->getElo($this->getKit())->getElo();
		$loserElo = $loser->getElo($this->getKit())->getElo();

		$winnerEstimate = (int) (10 ^ ($winnerElo / 400));
		$loserEstimate = (int) (10 ^ ($loserElo / 400));

		$winnerEstimatedElo = $winnerEstimate / ($winnerEstimate + $loserEstimate);
		$winnerKFactor = $winner->getKFactor($this->getKit());
		$winnerAddition = intval($winnerKFactor * (1 - $winnerEstimatedElo));

		$winner->boostElo($this->getKit(), $winnerAddition);
		$winner->saveElo($this->getKit());
		if($winner->isOnline()) {
			$winner->sendArgsMessage(TextFormat::GREEN."You gained {0} ELO!", $winnerAddition);
			$winner->sendArgsMessage(TextFormat::RED."Your ELO for {0}: {1}", $this->getKit()->getName(), $winner->getElo($this->getKit())->getElo());
		}
		$loserKFactor = $loser->getKFactor($this->getKit());

		$loserEstimatedElo = $loserEstimate / ($winnerEstimate + $loserEstimate);

		$loserSubtraction = intval($loserKFactor * (0 - $loserEstimatedElo));
		$loser->boostElo($this->getKit(), $loserSubtraction);
		$loser->saveElo($this->getKit());

		if($loser->isOnline()) {
			$loser->sendArgsMessage(TextFormat::RED."You lost {0} ELO!", $loserSubtraction);
			$loser->sendArgsMessage(TextFormat::RED."Your ELO for {0}: {1}", $this->getKit()->getName(), $loser->getElo($this->getKit())->getElo());
		}

	}

}