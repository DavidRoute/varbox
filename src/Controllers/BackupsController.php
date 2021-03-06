<?php

namespace Varbox\Controllers;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Varbox\Contracts\BackupFilterContract;
use Varbox\Contracts\BackupModelContract;
use Varbox\Contracts\BackupSortContract;
use Varbox\Traits\CanCrud;

class BackupsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use CanCrud;

    /**
     * @var BackupModelContract
     */
    protected $model;

    /**
     * BackupsController constructor.
     *
     * @param BackupModelContract $model
     */
    public function __construct(BackupModelContract $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param BackupFilterContract $filter
     * @param BackupSortContract $sort
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request, BackupFilterContract $filter, BackupSortContract $sort)
    {
        return $this->_index(function () use ($request, $filter, $sort) {
            $this->items = $this->model
                ->filtered($request->all(), $filter)
                ->sorted($request->all(), $sort)
                ->paginate(config('varbox.crud.per_page', 30));

            $this->title = 'Backups';
            $this->view = view('varbox::admin.backups.index');
            $this->vars = [
                'days' => config('varbox.backup.old_threshold', 30),
            ];
        });
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        try {
            $queueConnection = config('queue.default');
            $queueDriver = config('queue.connections.' . $queueConnection . '.driver');

            Artisan::queue('backup:run');

            if (in_array($queueDriver, ['sync', 'database'])) {
                flash()->success('The backup was successfully created!');
            } else {
                flash()->success('The process has been queued! Check back shortly to see your backup.');
            }
        } catch (Exception $e) {
            flash()->error($e->getMessage(), $e);
        }

        return redirect()->route('admin.backups.index');
    }

    /**
     * @param BackupModelContract $backup
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(BackupModelContract $backup)
    {
        try {
            return $backup->download();
        } catch (ModelNotFoundException $e) {
            flash()->error('You are trying to download a backup archive that does not exist!', $e);
            return redirect()->route('admin.backups.index');
        }
    }

    /**
     * @param BackupModelContract $backup
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(BackupModelContract $backup)
    {
        return $this->_destroy(function () use ($backup) {
            $this->redirect = redirect()->route('admin.backups.index');

            $backup->deleteRecordAndFile();
        });
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function clean()
    {
        try {
            $this->model->deleteOld();

            flash()->success('Old backups were successfully deleted!');
        } catch (ModelNotFoundException $e) {
            flash()->error('Something went wrong! Please try again.', $e);
        }

        return redirect()->route('admin.backups.index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function delete()
    {
        try {
            $this->model->deleteAll();

            flash()->success('All backups were successfully deleted!');
        } catch (ModelNotFoundException $e) {
            flash()->error('Something went wrong! Please try again.', $e);
        }

        return redirect()->route('admin.backups.index');
    }
}
