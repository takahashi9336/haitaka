<?php

namespace Core;

class Validator {
    private array $data;
    private array $errors = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function rule(string $rule, array $fields): void {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            switch ($rule) {
                case 'required':
                    if (empty($value) && $value !== '0') $this->errors[$field][] = "必須項目です。";
                    break;
                case 'date':
                    if (!empty($value) && !strtotime($value)) $this->errors[$field][] = "正しい日付形式ではありません。";
                    break;
            }
        }
    }

    public function validate(): bool { return empty($this->errors); }
    public function errors(): array { return $this->errors; }
}