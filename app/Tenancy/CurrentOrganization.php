<?php

namespace App\Tenancy;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use LogicException;

final class CurrentOrganization
{
    private ?Organization $organization = null;

    private ?OrganizationMembership $membership = null;

    public function set(
        Organization $organization,
        OrganizationMembership $membership
    ): void {
        $this->organization = $organization;
        $this->membership = $membership;
    }

    public function organization(): Organization
    {
        if (! $this->organization) {
            throw new LogicException('Current organization has not been resolved.');
        }

        return $this->organization;
    }

    public function membership(): OrganizationMembership
    {
        if (! $this->membership) {
            throw new LogicException('Current organization membership has not been resolved.');
        }

        return $this->membership;
    }

    public function id(): int
    {
        return $this->organization()->id;
    }

    public function role(): string
    {
        return $this->membership()->role;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }
}
