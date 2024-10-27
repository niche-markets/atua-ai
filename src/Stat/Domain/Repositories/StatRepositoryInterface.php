<?php

declare(strict_types=1);

namespace Stat\Domain\Repositories;

use DateTimeInterface;
use Shared\Domain\Repositories\RepositoryInterface;
use Stat\Domain\Entities\AbstractStatEntity;
use Stat\Domain\ValueObjects\DatasetCategory;
use Stat\Domain\ValueObjects\StatType;
use Traversable;

interface StatRepositoryInterface extends RepositoryInterface
{
    /**
     * Add a stat entity to the repository.
     * 
     * @param AbstractStatEntity $stat
     * @return static
     */
    public function add(AbstractStatEntity $stat): static;

    /**
     * Filter the stat entity by type.
     * 
     * @param StatType $type
     * @return static
     */
    public function filterByType(StatType $type): static;

    /**
     * Filter the stat entity by year.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByYear(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by month.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByMonth(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by day.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByDay(DateTimeInterface $date): static;

    /**
     * Get the total of the stat entity.
     * 
     * @return int
     */
    public function stat(): int;

    /**
     * Get the dataset for the stat entity.
     * 
     * @return Traversable<array{category:string,value:int}>
     */
    public function getDataset(DatasetCategory $type = DatasetCategory::DATE): Traversable;
}
