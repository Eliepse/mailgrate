<?php


namespace Eliepse;


use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;

final class Runtimer
{
    /***
     * @var Carbon
     */
    private $start_at;

    /**
     * @var Carbon
     */
    private $end_at;


    public function __construct(bool $autostart = false)
    {
        if ($autostart)
            $this->start();
    }


    public function start(): void
    {
        $this->start_at = $this->start_at ?: Carbon::now();
    }


    public function stop(): void
    {
        $this->end_at = $this->end_at ?: Carbon::now();
    }


    public function reset(): void
    {
        $this->start_at = Carbon::now();
        $this->end_at = null;
    }


    /**
     * @return CarbonInterval
     * @throws Exception
     */
    public function durationInterval(): CarbonInterval
    {
        if (!$this->start_at)
            return new CarbonInterval(0);

        $end_at = $this->end_at ?: Carbon::now();

        return $end_at->diffAsCarbonInterval($this->start_at);
    }


    /**
     * @return int
     * @throws Exception
     */
    public function durationMilliseconds(): int
    {
        return $this->durationInterval()->totalMilliseconds;
    }


    /**
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        return $this->durationInterval()->format("%h h %i min %s s (+%F µs)");
    }
}
