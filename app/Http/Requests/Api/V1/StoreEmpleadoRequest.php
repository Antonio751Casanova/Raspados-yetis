<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: StoreEmpleadoRequest
 *
 * Valida los datos para crear un nuevo empleado.
 * Módulo E — Recursos Humanos
 */
class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'             => ['required', 'string', 'max:100'],
            'apellido_paterno'   => ['required', 'string', 'max:100'],
            'apellido_materno'   => ['nullable', 'string', 'max:100'],
            'telefono'           => ['nullable', 'string', 'max:20'],
            'correo'             => ['nullable', 'email', 'max:150', 'unique:empleados,correo'],
            'direccion'          => ['nullable', 'string', 'max:500'],
            'fecha_ingreso'      => ['required', 'date'],
            'puesto'             => ['nullable', 'string', 'max:100'],
            'salario_por_evento' => ['nullable', 'numeric', 'min:0'],
            'estado'             => ['nullable', Rule::in(['activo', 'inactivo'])],
            'fotografia'         => ['nullable', 'image', 'max:2048'], // 2MB
            'notas'              => ['nullable', 'string', 'max:1000'],
            'roles'              => ['nullable', 'array'],
            'roles.*'            => ['integer', 'exists:roles_staff,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'correo.unique'             => 'Este correo ya está registrado.',
            'fecha_ingreso.required'    => 'La fecha de ingreso es obligatoria.',
            'fotografia.image'          => 'El archivo debe ser una imagen.',
            'fotografia.max'            => 'La imagen no puede superar 2 MB.',
        ];
    }
}
