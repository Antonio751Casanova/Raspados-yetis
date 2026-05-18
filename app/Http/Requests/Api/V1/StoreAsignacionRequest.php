<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AsignacionEvento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: StoreAsignacionRequest
 *
 * Valida los datos para asignar un empleado a un evento.
 * Módulo E — Recursos Humanos
 */
class StoreAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empleado_id'       => ['required', 'integer', 'exists:empleados,id'],
            'evento_id'         => ['required', 'integer', 'exists:eventos,id'],
            'rol_staff_id'      => ['nullable', 'integer', 'exists:roles_staff,id'],
            'rol_asignado'      => ['nullable', 'string', 'max:80'],
            'hora_inicio'       => ['nullable', 'date_format:H:i'],
            'hora_fin'          => ['nullable', 'date_format:H:i', 'after:hora_inicio'],
            'estado_asistencia' => ['nullable', Rule::in(AsignacionEvento::ESTADOS)],
            'observaciones'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'empleado_id.exists' => 'El empleado seleccionado no existe.',
            'evento_id.exists'   => 'El evento seleccionado no existe.',
            'hora_fin.after'     => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
