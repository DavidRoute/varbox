<?php

namespace Varbox\Contracts;

interface BackupModelContract
{
    /**
     * @return string
     */
    public function getSizeInMbAttribute();

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeAlphabetically($query);

    /**
     * @return bool
     */
    public function local();

    /**
     * @return int|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download();

    /**
     * @return void
     */
    public function deleteAll();

    /**
     * @return void
     */
    public function deleteOld();

    /**
     * @throws \Exception
     * @return void
     */
    public function deleteFromDatabaseAndFilesystem();
}
