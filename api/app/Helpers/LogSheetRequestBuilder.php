<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LogSheetRequestBuilder
{
    public static function definition(string $resource): array
    {
        $definition = LogSheetResourceRegistry::find($resource);

        if (! $definition) {
            throw ValidationException::withMessages([
                'resource' => ['The selected resource is not supported.'],
            ]);
        }

        return $definition;
    }

    public static function validatedPayload(Request $request, array $definition, bool $partial = false): array
    {
        $rules = collect($definition['rules'])
            ->only($definition['fields'])
            ->map(function (array $rule) use ($partial) {
                if (! $partial) {
                    return $rule;
                }

                $withoutRequired = array_values(array_filter($rule, fn (string $item) => $item !== 'required'));

                return array_merge(['sometimes'], $withoutRequired);
            })
            ->all();

        $validator = Validator::make($request->all(), $rules);
        $validator->validate();

        return collect($validator->validated())
            ->only($definition['fields'])
            ->all();
    }

    public static function withAuthenticatedOperator(array $payload, array $definition, Request $request): array
    {
        if (! in_array('operator_name', $definition['fields'], true)) {
            return $payload;
        }

        $user = $request->attributes->get('auth_user');

        if (! $user || ! isset($user->full_name)) {
            return $payload;
        }

        $payload['operator_name'] = $user->full_name;

        return $payload;
    }
}
