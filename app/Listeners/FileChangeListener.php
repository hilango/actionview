<?php
namespace App\Listeners;

use App\Events\Event;
use App\Events\FileUploadEvent;
use App\Events\FileDelEvent;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use DB;

class FileChangeListener 
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    /**
     * Handle the event.
     *
     * @param  FileChangeEvent  $event
     * @return void
     */
    public function handle(Event $event)
    {
        if ($event instanceof FileUploadEvent)
        {
            $this->updIssueField($event->project_key, $event->issue_id, $event->field_key, $event->file_id, 1);
        }
        else if ($event instanceof FileDelEvent)
        {
            $this->updIssueField($event->project_key, $event->issue_id, $event->field_key, $event->file_id, 2);
        }
    }
    /**
     * update the issue file field.
     *
     * @param  string  $project_key
     * @param  string  $issue_id
     * @param  string  $field_key
     * @param  string  $file_id
     * @param  int flag
     * @return void
     */
    public function updIssueField($project_key, $issue_id, $field_key, $file_id, $flag)
    {
        $table = 'issue_' . $project_key;
        $issue = DB::collection($table)->where('_id', $issue_id)->first();

        if (!is_array($issue[$field_key]))
        {
            $issue[$field_key] = [];
        }
        if ($flag == 1)
        {
            array_push($issue[$field_key], $file_id);
        } 
        else 
        {
            $index = array_search($file_id, $issue[$field_key]);
            if ($index !== false)
            {
                array_splice($issue[$field_key], $index, 1);
            }
        }

        $issue['updated_at'] = time();
        // update issue file field
        DB::collection($table)->where('_id', $issue_id)->update(array_except($issue, [ '_id' ]));
        // create tag
        DB::collection('issue_his_' . $project_key)->insert([ 'issue_id' => $issue['_id']->__toString(), 'stamptime' => time() ] + array_except($issue, [ '_id' ]));
    }
}
