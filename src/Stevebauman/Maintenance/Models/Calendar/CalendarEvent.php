<?php namespace Stevebauman\Maintenance\Models;

use Carbon\Carbon;
use Stevebauman\Maintenance\Models\BaseModel;

class CalendarEvent extends BaseModel {

    protected $table = 'calendar_events';
    
    protected $viewer = 'Stevebauman\Maintenance\Viewers\CalendarEventViewer';
    
    protected $fillable = array(
        'parent_id',
        'user_id', 
        'title', 
        'description', 
        'start',
        'end',
        'allDay',
        'color',
        'background_color',
        'recur_frequency',
        'recur_filter_days',
        'recur_filter_months',
        'recur_filter_years',
    );
    
    public function user()
    {
        return $this->hasOne('Stevebauman\Maintenance\Models\User', 'id', 'user_id');
    }
    
    public function calendar()
    {
        return $this->hasOne('Stevebauman\Maintenance\Models\Calendar', 'id', 'calendar_id');
    }
    
    public function assets()
    {
        return $this->belongsToMany('Stevebauman\Maintenance\Models\Asset', 'asset_events', 'event_id', 'asset_id')->withTimestamps();
    }
    
    /**
     * Checks if the current event is an all day event
     * 
     * @return boolean
     */
    public function isAllDay()
    {
        if(isset($this->allDay) && !empty($this->allDay)){
            return true;
        } return false;
    }
    
    /**
     * Checks if the current event is recurring
     * 
     * @return boolean
     */
    public function isRecurring()
    {
        if(isset($this->recur_frequency) && !empty($this->recur_frequency)){
            return true;
        } return false;
    }
    
    /**
     * Checks if the current event is a recurrence or not by checking if the parent_id is set
     * 
     * @return boolean
     */
    public function isRecurrence()
    {
        if(isset($this->parent_id) && !empty($this->parent_id)) {
            return true;
        } return false;
    }
    
    /** 
     * Overrides Stevebauman\Maintenance\Modles\BaseModel@getCreatedAtAttribute due to FullCalendar dependencies
     * 
     * @param type $created_at
     * @return type
     */
    public function getCreatedAtAttribute($created_at)
    {
        return $created_at;
    }
    
    /**
     * Since you don't want to start an event recurring on the same day as the event (will cause duplicates),
     * depending on the recur_frequency attribute, we'll add the appropriate time 
     * onto the event start date using Carbon and then process recurrences from there.
     * 
     * @return null OR string
     */
    public function getRecurStartAttribute()
    {
        $recurStart = Carbon::parse($this->attributes['start']);
        
        if(isset($this->attributes['recur_frequency'])){
            
            switch($this->attributes['recur_frequency']){
                case 'DAILY':
                     return $recurStart->addDay();
                case 'WEEKLY':
                    return $recurStart->addWeek();
                case 'MONTHLY':
                    return $recurStart->addMonth();
                default:
                    return $recurStart->addYear();
            }
            
        } 
        
        return NULL;
        
    }
    
    /**
     * Offers same functionality as @getRecurStartAttribute, but instead add's time
     * onto the event end date instead of the start date.
     * 
     * @return null OR string
     */
    public function getRecurEndAttribute()
    {
        $recurStart = Carbon::parse($this->attributes['end']);
        
        if(isset($this->attributes['recur_frequency'])){
            
            switch($this->attributes['recur_frequency']){
                 case 'DAILY':
                     return $recurStart->addDay();
                case 'WEEKLY':
                    return $recurStart->addWeek();
                case 'MONTHLY':
                    return $recurStart->addMonth();
                default:
                    return $recurStart->addYear();
            }
            
        } 
        
        return NULL;
    }
    
    /**
     * Limits generation of recurrences to save processing time. 
     * Depending on the recurring frequency, we'll limit the maximum amount of recurrences
     * the user could possibly see in a one month calendar view.
     * 
     * @return integer
     */
    public function getRecurLimitAttribute()
    {
        
        switch($this->attributes['recur_frequency']){
            case 'DAILY':
                /*
                 * Generation of daily recurrences will be limited to 42 since the 
                 * Fullcalendar view of a month is a 6*7 (42) day view
                 */
                return 42;
           case 'WEEKLY':
               /*
                * Generation of weekly recurrences will be limited to 6 since a
                * maximum of 6 weeks can be displayed in Fullcalendar
                */
               return 6;
           case 'MONTHLY':
               /*
                * Generation of monthly recurrences will be limited to 2 since a
                * maximum of 2 months can be shown in Fullcalendar
                */
               return 2;
           default:
               /*
                * Default is 1 since either no recurring frequency is set, doesn't
                * equal a switch case, or the frequency is yearly
                */
               return 1;
       }
        
    }
    
    /**
     * Returns the start attribute formatted to an easier to read date/time
     * 
     * @return string
     */
    public function getStartFormattedAttribute()
    {
        return Carbon::parse($this->attributes['start'])->format('M dS Y - h:ia'); 
    }
    
    /**
     * Returns the end attribute formatted to an easier to read date/time
     * 
     * @return string
     */
    public function getEndFormattedAttribute()
    {
        return Carbon::parse($this->attributes['end'])->format('M dS Y - h:ia'); 
    }
    
    /**
     * Attribute for Pickadate compatibility
     * 
     * @return type
     */
    public function getStartDateAttribute()
    {
        return Carbon::parse($this->attributes['start'])->format('j F, Y'); 
    }
    
    /**
     * Attribute for Pickatime compatibility
     * 
     * @return type
     */
    public function getStartTimeAttribute()
    {
        return Carbon::parse($this->attributes['start'])->format('h:i A'); 
    }
    
    /**
     * Attribute for Pickadate compatibility
     * 
     * @return type
     */
    public function getEndDateAttribute()
    {
        return Carbon::parse($this->attributes['end'])->format('j F, Y');
    }
    
    /**
     * Attribute for Pickatime compatibility
     * 
     * @return type
     */
    public function getEndTimeAttribute()
    {
        return Carbon::parse($this->attributes['end'])->format('h:i A'); 
    }
    
    /**
     * Displays All Day attribute in a label
     * 
     * @return type
     */
    public function getAlldayLabelAttribute()
    {
        $all_day = $this->attributes['allDay'];
        
        $label = '<span class="label label-%s">%s</span>';
        
        switch($all_day){
            case 0:
                return sprintf($label, 'danger', 'No');
            case 1:
                return sprintf($label, 'success', 'Yes');
        }
    }
    
    /**
     * Returns the parent event of a recurrence
     * 
     * @return object
     */
    public function getParent()
    {
        if($this->isRecurrence()){
            return $this->find($this->parent_id);
        }
    }
    
}