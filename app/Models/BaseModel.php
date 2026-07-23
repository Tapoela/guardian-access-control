<?php

namespace App\Models;

use CodeIgniter\Model;

class BaseModel extends Model
{
    /**
     * Return only active records.
     */
    public function active()
    {
        return $this->where('IsActive', 1);
    }

    /**
     * Return only inactive records.
     */
    public function inactive()
    {
        return $this->where('IsActive', 0);
    }

    /**
     * Filter records by site.
     */
    public function forSite(int $siteId)
    {
        return $this->where('FkSiteId', $siteId);
    }

    /**
     * Return records ordered by display order.
     */
    public function ordered()
    {
        return $this->orderBy('DisplayOrder', 'ASC');
    }
}