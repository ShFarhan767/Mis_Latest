<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Area;
use App\Models\BusinessType;
use App\Models\Country;
use App\Models\LeadSource;
use App\Models\InterestLevel;
use App\Models\ServiceType;

class CacheService
{
    // Cache for 24 hours (86400 seconds)
    private const CACHE_DURATION = 86400;

    /**
     * Get all areas with caching
     */
    public static function getAreas()
    {
        return Cache::remember('areas_all', self::CACHE_DURATION, function () {
            return Area::where('status', 'Running')
                ->orderBy('area_name')
                ->get(['id', 'area_name', 'country_name', 'status'])
                ->toArray();
        });
    }

    /**
     * Get all business types with caching
     */
    public static function getBusinessTypes()
    {
        return Cache::remember('business_types_all', self::CACHE_DURATION, function () {
            return BusinessType::where('status', 'Running')
                ->orderBy('name')
                ->get(['id', 'name', 'status'])
                ->toArray();
        });
    }

    /**
     * Get all countries with caching
     */
    public static function getCountries()
    {
        return Cache::remember('countries_all', self::CACHE_DURATION, function () {
            return Country::where('status', 'Running')
                ->orderBy('country_name')
                ->get(['id', 'country_name', 'status'])
                ->toArray();
        });
    }

    /**
     * Get all lead sources with caching
     */
    public static function getLeadSources()
    {
        return Cache::remember('lead_sources_all', self::CACHE_DURATION, function () {
            return LeadSource::where('status', 'Running')
                ->orderBy('lead_source_name')
                ->get(['id', 'lead_source_name', 'status'])
                ->toArray();
        });
    }

    /**
     * Get all interest levels with caching
     */
    public static function getInterestLevels()
    {
        return Cache::remember('interest_levels_all', self::CACHE_DURATION, function () {
            return InterestLevel::orderBy('level_name')
                ->get(['id', 'level_name'])
                ->toArray();
        });
    }

    /**
     * Get all service types with caching
     */
    public static function getServiceTypes()
    {
        return Cache::remember('service_types_all', self::CACHE_DURATION, function () {
            return ServiceType::where('status', 'Running')
                ->orderBy('service_type_name')
                ->get(['id', 'service_type_name', 'status'])
                ->toArray();
        });
    }

    /**
     * Invalidate area cache
     */
    public static function invalidateAreas()
    {
        Cache::forget('areas_all');
    }

    /**
     * Invalidate business type cache
     */
    public static function invalidateBusinessTypes()
    {
        Cache::forget('business_types_all');
    }

    /**
     * Invalidate country cache
     */
    public static function invalidateCountries()
    {
        Cache::forget('countries_all');
    }

    /**
     * Invalidate lead source cache
     */
    public static function invalidateLeadSources()
    {
        Cache::forget('lead_sources_all');
    }

    /**
     * Invalidate all caches
     */
    public static function invalidateAll()
    {
        Cache::forget('areas_all');
        Cache::forget('business_types_all');
        Cache::forget('countries_all');
        Cache::forget('lead_sources_all');
        Cache::forget('interest_levels_all');
        Cache::forget('service_types_all');
    }
}
