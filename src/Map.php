<?php

namespace KamranAhmed\Walkers;

use KamranAhmed\Walkers\Exceptions\InvalidLevelException;
use KamranAhmed\Walkers\Player\Interfaces\Player;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Map
 *
 * @package KamranAhmed\Walkers
 */
class Map
{
    /** @var int */
    protected $level;

    /** @var SymfonyStyle */
    protected $io;

    /** @var Player */
    protected $player;

    /** @var array */
    protected $levelDetail;

    /**
     * Map constructor.
     *
     * @param \Symfony\Component\Console\Style\SymfonyStyle $io
     * @param \KamranAhmed\Walkers\Player\Interfaces\Player $player
     * @param int                                           $level
     */
    public function __construct(SymfonyStyle $io, Player $player = null, $level = 0)
    {
        $this->io     = $io;
        $this->player = $player;

        $this->populateLevel($level);
    }

    public function play()
    {
        $this->initialize();

        $this->io->text('You will be shown some doors! Carefully choose a door while praying that you do not come across a Walker!');
        $this->io->text('Say your prayers and press any key to continue ..');

        fgetc(STDIN);

        do {
            $doors = $this->generateDoors();

            $choice = $this->io->choice('Carefully choose the door to enter!', array_keys($doors));

            // If there was nothing in the door
            if (empty($doors[$choice])) {
                $this->populateLevel(++$this->level);
                continue;
            } else {
                $this->io->warning('Woah! You have came across a zombie');
                fgetc(STDIN);
            }

        } while ($this->player->isAlive());

        // while ($this->player->isAlive()) {
        // }
    }

    protected function initialize()
    {
        if (empty($this->level)) {
            $this->showWelcome();
        }

        if (empty($this->player)) {
            $this->choosePlayer();
        }
    }

    public function generateDoors()
    {
        $totalDoors = intval($this->levelDetail['doorCount'] ?? 3);
        $walkers    = $this->levelDetail['walkers'] ?? [];

        if (count($walkers) === $totalDoors) {
            throw new InvalidLevelException('Door count must be less than walker count');
        }

        // Create allowed number of doors
        $doors = array_fill(0, $totalDoors, false);

        foreach ($walkers as $counter => $walker) {
            $doors[$counter] = new $walker;
        }

        shuffle($doors);

        $namedDoors = [];
        foreach ($doors as $counter => $door) {
            $namedDoors['Door # ' . $counter] = $door;
        }

        return $namedDoors;
    }

    public function choosePlayer()
    {
        // TODO : Empty check
        $players = $this->levelDetail['players'] ?? [];

        $choice = $this->io->choice('Chose your player?', array_keys($players));

        $this->player = new $players[$choice];

        $this->io->title('Godspeed ' . $this->player->getName() . '!');
    }

    public function populateLevel(int $level)
    {
        $map = require __DIR__ . '/../config/map.php';

        if (empty($map['levels'][$level])) {

            // If it was level `0` and we even don't have that
            if (empty($level)) {
                throw new InvalidLevelException('Specified level does not exist ' . $level);
            }

            // Any other level and it doesn't exist means end has been reached
            $this->showCompletionExit();
        }

        $this->level       = $level;
        $this->levelDetail = $map['levels'][$level];
    }

    public function showWelcome()
    {
        $this->io->title('The Walking Dead');
        $this->io->text('Welcome to the world of the dead, see if you can ditch your way through the walkers towards the sanctuary.');
    }

    public function showCompletionExit()
    {
        $this->io->success('Good work ' . $this->player->getName() . '! You have made it alive through the other end');
        exit();
    }
}
