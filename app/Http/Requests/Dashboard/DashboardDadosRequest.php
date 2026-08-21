<?php

namespace App\Http\Requests\Dashboard;

use App\Enum\PeriodoDashboard;
use App\Enum\WidgetDashboard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardDadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'widgets' => ['required', 'array', 'min:1'],
            'widgets.*' => ['required', 'string', Rule::in(WidgetDashboard::valores())],
            'periodo' => ['nullable', 'string', Rule::in(PeriodoDashboard::valores())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $widgets = $this->input('widgets');

        if (is_string($widgets)) {
            $widgets = array_values(array_filter(array_map('trim', explode(',', $widgets))));
        }

        if (is_array($widgets)) {
            $this->merge([
                'widgets' => array_values(array_unique($widgets)),
            ]);
        }
    }
}
