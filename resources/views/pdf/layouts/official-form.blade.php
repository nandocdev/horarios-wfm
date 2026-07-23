{{-- resources/views/pdf/layouts/official-form.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Formulario Oficial')</title>
    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .form-container {
            border: 1.5px solid #000;
            padding: 10px;
        }

        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            line-height: 14px;
        }

        .field-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .bordered td,
        .bordered th {
            border: 1px solid #000;
            padding: 4px;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 8px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 8px 0;
        }
    </style>
</head>

<body>
    <div class="form-container">
        @yield('content')
    </div>
</body>

</html>