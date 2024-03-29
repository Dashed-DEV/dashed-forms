<?php

namespace Dashed\DashedForms\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormInput extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $fillable = [
        'name',
    ];

    protected $table = 'dashed__form_inputs';

    protected $casts = [
        'content' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormInputField::class);
    }

    public function scopeUnviewed($query)
    {
        $query->where('viewed', 0);
    }
}
