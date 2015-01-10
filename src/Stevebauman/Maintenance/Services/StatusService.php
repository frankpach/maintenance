<?php

namespace Stevebauman\Maintenance\Services;

use Stevebauman\Maintenance\Services\SentryService;
use Stevebauman\Maintenance\Models\Status;
use Stevebauman\Maintenance\Services\BaseModelService;

class StatusService extends BaseModelService
{

    public function __construct(Status $status, SentryService $sentry)
    {
        $this->model = $status;
        $this->sentry = $sentry;
    }

    public function create()
    {
        $this->dbStartTransaction();

        try {

            $insert = array(
                'user_id' => $this->sentry->getCurrentUserId(),
                'name' => $this->getInput('name'),
                'color' => $this->getInput('color')
            );

            $record = $this->model->create($insert);

            $this->dbCommitTransaction();

            return $record;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }
    }

    public function update($id)
    {

        $this->dbStartTransaction();

        try {

            $insert = array(
                'name' => $this->getInput('name'),
                'color' => $this->getInput('color')
            );

            $record = $this->find($id);

            if ($record->update($insert)) {

                $this->dbCommitTransaction();

                return $record;
            }

            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }
    }

    public function firstOrCreateRequest()
    {

        $this->dbStartTransaction();

        try {

            $insert = array(
                'name' => 'Requested',
                'color' => 'default'
            );

            $record = $this->model->firstOrCreate($insert);

            $this->dbCommitTransaction();

            return $record;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }
    }

}