<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */

namespace App\Entity\Parts\PartTraits;

use App\Entity\Parts\Part;
use App\Security\Annotations\ColumnSecurity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Advanced properties of a part, not related to a more specific group.
 */
trait AdvancedPropertyTrait
{
    /**
     * @var bool Determines if this part entry needs review (for example, because it is work in progress)
     * @ORM\Column(type="boolean")
     * @ColumnSecurity(type="boolean")
     */
    protected bool $needs_review = false;

    /**
     * @var string a comma separated list of tags, associated with the part
     * @ORM\Column(type="text")
     * @ColumnSecurity(type="string", prefix="tags", placeholder="")
     */
    protected string $tags = '';

    /**
     * @var float|null how much a single part unit weighs in grams
     * @ORM\Column(type="float", nullable=true)
     * @ColumnSecurity(type="float", placeholder=null)
     * @Assert\PositiveOrZero()
     */
    protected ?float $mass = null;

    /**
     * Checks if this part is marked, for that it needs further review.
     */
    public function isNeedsReview(): bool
    {
        return $this->needs_review;
    }

    /**
     * Sets the "needs review" status of this part.
     *
     * @param bool $needs_review The new status
     *
     * @return Part|self
     */
    public function setNeedsReview(bool $needs_review): self
    {
        $this->needs_review = $needs_review;

        return $this;
    }

    /**
     * Gets a comma separated list, of tags, that are assigned to this part.
     */
    public function getTags(): string
    {
        return $this->tags;
    }

    /**
     * Sets a comma separated list of tags, that are assigned to this part.
     *
     * @param string $tags The new tags
     *
     * @return $this
     */
    public function setTags(string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Returns the mass of a single part unit.
     * Returns null, if the mass is unknown/not set yet.
     */
    public function getMass(): ?float
    {
        return $this->mass;
    }

    /**
     * Sets the mass of a single part unit.
     * Sett to null, if the mass is unknown.
     *
     * @param float|null $mass the new mass
     *
     * @return $this
     */
    public function setMass(?float $mass): self
    {
        $this->mass = $mass;

        return $this;
    }
}
