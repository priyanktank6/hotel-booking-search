<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1|max:6',
            'meal_plan' => 'required|in:room_only,breakfast_included',
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required' => 'Check-in date is required',
            'check_in.after_or_equal' => 'Check-in date cannot be in the past',
            'check_out.required' => 'Check-out date is required',
            'check_out.after' => 'Check-out date must be after check-in date',
            'adults.required' => 'Number of adults is required',
            'adults.min' => 'At least 1 adult is required',
            'adults.max' => 'Maximum 6 adults allowed',
            'meal_plan.required' => 'Meal plan selection is required',
            'meal_plan.in' => 'Invalid meal plan selected',
        ];
    }
}