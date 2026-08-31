<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines (ru)
|--------------------------------------------------------------------------
|
| Полный перевод lang/en/validation.php. Набор ключей обязан совпадать с
| английским файлом один в один: пропущенный ключ показывает пользователю
| английскую строку (или сам ключ), и заметить это в тестах нечем.
|
*/

return [

    'accepted' => 'Нужно принять :attribute.',
    'accepted_if' => 'Нужно принять :attribute, когда :other равно :value.',
    'active_url' => 'Поле :attribute должно быть корректной ссылкой.',
    'after' => 'Поле :attribute должно содержать дату после :date.',
    'after_or_equal' => 'Поле :attribute должно содержать дату не раньше :date.',
    'alpha' => 'Поле :attribute может содержать только буквы.',
    'alpha_dash' => 'Поле :attribute может содержать только буквы, цифры, дефис и подчёркивание.',
    'alpha_num' => 'Поле :attribute может содержать только буквы и цифры.',
    'any_of' => 'Поле :attribute заполнено неверно.',
    'array' => 'Поле :attribute должно быть массивом.',
    'ascii' => 'Поле :attribute может содержать только однобайтовые буквы, цифры и символы.',
    'before' => 'Поле :attribute должно содержать дату до :date.',
    'before_or_equal' => 'Поле :attribute должно содержать дату не позже :date.',
    'between' => [
        'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
        'file' => 'Размер файла в поле :attribute должен быть от :min до :max килобайт.',
        'numeric' => 'Поле :attribute должно быть от :min до :max.',
        'string' => 'Поле :attribute должно содержать от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно быть true или false.',
    'can' => 'Поле :attribute содержит недопустимое значение.',
    'confirmed' => 'Поле :attribute не совпадает с подтверждением.',
    'contains' => 'В поле :attribute не хватает обязательного значения.',
    'current_password' => 'Неверный пароль.',
    'date' => 'Поле :attribute должно содержать корректную дату.',
    'date_equals' => 'Поле :attribute должно содержать дату, равную :date.',
    'date_format' => 'Поле :attribute должно соответствовать формату :format.',
    'decimal' => 'Поле :attribute должно содержать :decimal знаков после запятой.',
    'declined' => 'Поле :attribute должно быть отклонено.',
    'declined_if' => 'Поле :attribute должно быть отклонено, когда :other равно :value.',
    'different' => 'Поля :attribute и :other должны различаться.',
    'digits' => 'Поле :attribute должно содержать :digits цифр.',
    'digits_between' => 'Поле :attribute должно содержать от :min до :max цифр.',
    'dimensions' => 'Поле :attribute содержит изображение с недопустимыми размерами.',
    'distinct' => 'Поле :attribute содержит повторяющееся значение.',
    'doesnt_contain' => 'Поле :attribute не должно содержать ни одно из значений: :values.',
    'doesnt_end_with' => 'Поле :attribute не должно заканчиваться на одно из значений: :values.',
    'doesnt_start_with' => 'Поле :attribute не должно начинаться с одного из значений: :values.',
    'email' => 'Поле :attribute должно содержать корректный email.',
    'encoding' => 'Поле :attribute должно быть в кодировке :encoding.',
    'ends_with' => 'Поле :attribute должно заканчиваться на одно из значений: :values.',
    'enum' => 'Выбранное значение для :attribute некорректно.',
    'exists' => 'Выбранное значение для :attribute некорректно.',
    'extensions' => 'Поле :attribute должно иметь одно из расширений: :values.',
    'file' => 'Поле :attribute должно содержать файл.',
    'filled' => 'Поле :attribute не может быть пустым.',
    'gt' => [
        'array' => 'Поле :attribute должно содержать больше :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть больше :value килобайт.',
        'numeric' => 'Поле :attribute должно быть больше :value.',
        'string' => 'Поле :attribute должно содержать больше :value символов.',
    ],
    'gte' => [
        'array' => 'Поле :attribute должно содержать :value элементов или больше.',
        'file' => 'Размер файла в поле :attribute должен быть не меньше :value килобайт.',
        'numeric' => 'Поле :attribute должно быть не меньше :value.',
        'string' => 'Поле :attribute должно содержать не меньше :value символов.',
    ],
    'hex_color' => 'Поле :attribute должно содержать корректный HEX-цвет.',
    'image' => 'Поле :attribute должно содержать изображение.',
    'in' => 'Выбранное значение для :attribute некорректно.',
    'in_array' => 'Значение поля :attribute должно присутствовать в :other.',
    'in_array_keys' => 'Поле :attribute должно содержать хотя бы один из ключей: :values.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'ip' => 'Поле :attribute должно содержать корректный IP-адрес.',
    'ipv4' => 'Поле :attribute должно содержать корректный IPv4-адрес.',
    'ipv6' => 'Поле :attribute должно содержать корректный IPv6-адрес.',
    'json' => 'Поле :attribute должно содержать корректную JSON-строку.',
    'list' => 'Поле :attribute должно быть списком.',
    'lowercase' => 'Поле :attribute должно быть в нижнем регистре.',
    'lt' => [
        'array' => 'Поле :attribute должно содержать меньше :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть меньше :value килобайт.',
        'numeric' => 'Поле :attribute должно быть меньше :value.',
        'string' => 'Поле :attribute должно содержать меньше :value символов.',
    ],
    'lte' => [
        'array' => 'Поле :attribute должно содержать не больше :value элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не больше :value килобайт.',
        'numeric' => 'Поле :attribute должно быть не больше :value.',
        'string' => 'Поле :attribute должно содержать не больше :value символов.',
    ],
    'mac_address' => 'Поле :attribute должно содержать корректный MAC-адрес.',
    'max' => [
        'array' => 'Поле :attribute должно содержать не больше :max элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не больше :max килобайт.',
        'numeric' => 'Поле :attribute должно быть не больше :max.',
        'string' => 'Поле :attribute должно содержать не больше :max символов.',
    ],
    'max_digits' => 'Поле :attribute должно содержать не больше :max цифр.',
    'mimes' => 'Поле :attribute должно содержать файл типа: :values.',
    'mimetypes' => 'Поле :attribute должно содержать файл типа: :values.',
    'min' => [
        'array' => 'Поле :attribute должно содержать не меньше :min элементов.',
        'file' => 'Размер файла в поле :attribute должен быть не меньше :min килобайт.',
        'numeric' => 'Поле :attribute должно быть не меньше :min.',
        'string' => 'Поле :attribute должно содержать не меньше :min символов.',
    ],
    'min_digits' => 'Поле :attribute должно содержать не меньше :min цифр.',
    'missing' => 'Поле :attribute должно отсутствовать.',
    'missing_if' => 'Поле :attribute должно отсутствовать, когда :other равно :value.',
    'missing_unless' => 'Поле :attribute должно отсутствовать, если :other не равно :value.',
    'missing_with' => 'Поле :attribute должно отсутствовать, когда передано :values.',
    'missing_with_all' => 'Поле :attribute должно отсутствовать, когда переданы :values.',
    'multiple_of' => 'Поле :attribute должно быть кратно :value.',
    'not_in' => 'Выбранное значение для :attribute некорректно.',
    'not_regex' => 'Поле :attribute имеет некорректный формат.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один спецсимвол.',
        'uncompromised' => 'Значение поля :attribute встречалось в утечке данных. Выберите другое значение.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'present_if' => 'Поле :attribute должно присутствовать, когда :other равно :value.',
    'present_unless' => 'Поле :attribute должно присутствовать, если :other не равно :value.',
    'present_with' => 'Поле :attribute должно присутствовать, когда передано :values.',
    'present_with_all' => 'Поле :attribute должно присутствовать, когда переданы :values.',
    'prohibited' => 'Поле :attribute запрещено.',
    'prohibited_if' => 'Поле :attribute запрещено, когда :other равно :value.',
    'prohibited_if_accepted' => 'Поле :attribute запрещено, когда :other принято.',
    'prohibited_if_declined' => 'Поле :attribute запрещено, когда :other отклонено.',
    'prohibited_unless' => 'Поле :attribute запрещено, если :other не входит в :values.',
    'prohibits' => 'Поле :attribute запрещает передавать :other.',
    'regex' => 'Поле :attribute имеет некорректный формат.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать записи для: :values.',
    'required_if' => 'Поле :attribute обязательно, когда :other равно :value.',
    'required_if_accepted' => 'Поле :attribute обязательно, когда :other принято.',
    'required_if_declined' => 'Поле :attribute обязательно, когда :other отклонено.',
    'required_unless' => 'Поле :attribute обязательно, если :other не входит в :values.',
    'required_with' => 'Поле :attribute обязательно, когда передано :values.',
    'required_with_all' => 'Поле :attribute обязательно, когда переданы :values.',
    'required_without' => 'Поле :attribute обязательно, когда не передано :values.',
    'required_without_all' => 'Поле :attribute обязательно, когда не передано ни одно из :values.',
    'same' => 'Поле :attribute должно совпадать с :other.',
    'size' => [
        'array' => 'Поле :attribute должно содержать :size элементов.',
        'file' => 'Размер файла в поле :attribute должен быть :size килобайт.',
        'numeric' => 'Поле :attribute должно быть равно :size.',
        'string' => 'Поле :attribute должно содержать :size символов.',
    ],
    'starts_with' => 'Поле :attribute должно начинаться с одного из значений: :values.',
    'string' => 'Поле :attribute должно быть строкой.',
    'timezone' => 'Поле :attribute должно содержать корректный часовой пояс.',
    'unique' => 'Такое значение поля :attribute уже занято.',
    'uploaded' => 'Не удалось загрузить файл в поле :attribute.',
    'uppercase' => 'Поле :attribute должно быть в верхнем регистре.',
    'url' => 'Поле :attribute должно содержать корректную ссылку.',
    'ulid' => 'Поле :attribute должно содержать корректный ULID.',
    'uuid' => 'Поле :attribute должно содержать корректный UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [],

];
