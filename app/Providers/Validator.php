<?php
namespace App\providers;

use App\models\MembreModel;

class Validator
{
    private $errors = [];
    private $fields = [];

    public function field(string $name, $value)
    {
        $this->fields[$name] = ['value' => $value, 'rules' => []];
        return $this;
    }

    public function required(): self
    {
        $field = end($this->fields);
        $name = key($this->fields);
        $field['rules'][] = ['name' => 'required', 'message' => "Le champ {$name} est requis."];
        $this->fields[$name] = $field;
        return $this;
    }

    public function min(int $length): self
    {
        $field = end($this->fields);
        $name = key($this->fields);
        $field['rules'][] = ['name' => 'min', 'value' => $length, 'message' => "Le champ {$name} doit avoir au moins {$length} caractères."];
        $this->fields[$name] = $field;
        return $this;
    }

    public function max(int $length): self
    {
        $field = end($this->fields);
        $name = key($this->fields);
        $field['rules'][] = ['name' => 'max', 'value' => $length, 'message' => "Le champ {$name} ne peut pas dépasser {$length} caractères."];
        $this->fields[$name] = $field;
        return $this;
    }

    public function email(): self
    {
        $field = end($this->fields);
        $name = key($this->fields);
        $field['rules'][] = ['name' => 'email', 'message' => "Le champ {$name} doit être une adresse email valide."];
        $this->fields[$name] = $field;
        return $this;
    }

    public function unique(MembreModel $model, string $column): self
    {
        $field = end($this->fields);
        $name = key($this->fields);
        $field['rules'][] = ['name' => 'unique', 'model' => $model, 'column' => $column, 'message' => "Le {$name} est déjà utilisé."];
        $this->fields[$name] = $field;
        return $this;
    }

    public function isSuccess(): bool
    {
        $this->errors = [];
        foreach ($this->fields as $name => $field) {
            foreach ($field['rules'] as $rule) {
                switch ($rule['name']) {
                    case 'required':
                        if (empty($field['value'])) {
                            $this->errors[$name] = $rule['message'];
                        }
                        break;
                    case 'min':
                        if (strlen($field['value']) < $rule['value']) {
                            $this->errors[$name] = $rule['message'];
                        }
                        break;
                    case 'max':
                        if (strlen($field['value']) > $rule['value']) {
                            $this->errors[$name] = $rule['message'];
                        }
                        break;
                    case 'email':
                        if (!filter_var($field['value'], FILTER_VALIDATE_EMAIL)) {
                            $this->errors[$name] = $rule['message'];
                        }
                        break;
                    case 'unique':
                        if ($rule['model']->unique($rule['column'], $field['value'])) {
                            $this->errors[$name] = $rule['message'];
                        }
                        break;
                }
            }
        }
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
