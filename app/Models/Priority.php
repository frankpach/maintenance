<?php

namespace App\Models;

use App\Models\Traits\HasUserTrait;
use App\Viewers\PriorityViewer;
use Orchestra\Support\Facades\HTML;

class Priority extends Model
{
    use HasUserTrait;

    /**
     * The priorities table.
     *
     * @var string
     */
    protected $table = 'priorities';

    /**
     * The priority viewer.
     *
     * @var string
     */
    protected $viewer = PriorityViewer::class;

    /**
     * The fillable priority attributes.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    /**
     * Finds or creates the default requested priority.
     *
     * @return Priority
     */
    public static function findOrCreateRequested()
    {
        return (new static())->firstOrCreate([
            'name'  => 'Requested',
            'color' => 'default',
        ]);
    }

    /**
     * Returns an html label with the color of the priority.
     *
     * @return string
     */
    public function getLabel()
    {
        $color = $this->color;

        return HTML::create('span', $this->name, ['class' => "label label-$color"]);
    }
}
