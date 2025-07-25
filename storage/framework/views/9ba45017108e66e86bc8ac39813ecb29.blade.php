<?php extract((new \Illuminate\Support\Collection($attributes->getAttributes()))->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>
@props(['field','id','label','labelSrOnly','helperText','hint','required','statePath'])
<x-filament-forms::field-wrapper :field="$field" :id="$id" :label="$label" :label-sr-only="$labelSrOnly" :helper-text="$helperText" :hint="$hint" :required="$required" :state-path="$statePath" >

{{ $slot ?? "" }}
</x-filament-forms::field-wrapper>