<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: UpdateEmpleadoRequest
 *
 * Valida los datos para actualizar un empleado existente.
 * Módulo E — Recursos Humanos
 */
class UpdateEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empleadoId = $this->route('empleado');

        return [
            'nombre'             => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_paterno'   => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_materno'   => ['nullable', 'string', 'max:100'],
            'telefono'           => ['nullable', 'string', 'max:20'],
            'correo'             => [
                'nullable', 'email', 'max:150',
                Rule::unique('empleados', 'correo')->ignore($empleadoId),
            ],
            'direccion'          => ['nullable', 'string', 'max:500'],
            'fecha_ingreso'      => ['sometimes', 'date'],
            'puesto'             => ['nullable', 'string', 'max:100'],
            'salario_por_evento' => ['nullable', 'numeric', 'min:0'],
            'estado'             => ['nullable', Rule::in(['activo', 'inactivo'])],
            'fotografia'         => ['nullable', 'image', 'max:2048'],
            'notas'              => ['nullable', 'string', 'max:1000'],
            'roles'              => ['nullable', 'array'],
            'roles.*'            => ['integer', 'exists:roles_staff,id'],
        ];
    }
}
