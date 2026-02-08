<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::withCount('vehicle')
            ->orderBy('registered_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($qb) use ($search) {
                $qb->where('DNI', 'LIKE', "%{$search}%")
                   ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('start')) {
            $query->whereDate('registered_at', '>=', $request->input('start'));
        }
        if ($request->filled('end')) {
            $query->whereDate('registered_at', '<=', $request->input('end'));
        }

        return Inertia::render('Clients/Index', [
            'clients' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['q', 'start', 'end']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'DNI' => [
                'required',
                'string',
                'unique:clients,DNI',
            ],
            'name' => 'required|string|min:2|max:255|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/',
            'email' => 'required|email|unique:clients,email|max:255',
            'telephone' => [
                'required',
                'string',
                'regex:/^(\+\d{1,4})\s*\d{6,15}$/',
                function ($attribute, $value, $fail) {
                    if (!$this->validateInternationalPhone($value)) {
                        $fail($this->getPhoneValidationError($value));
                    }
                },
            ],
            'city' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-\']+$/',
            'postal_code' => 'nullable|string|regex:/^[0-9]{5}$/',
            'registered_at' => 'required|date|before_or_equal:today',
        ], [
            'DNI.unique'       => 'Este documento ya está registrado.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'telephone.regex' => 'El formato del teléfono no es válido.',
            'city.regex' => 'La ciudad solo puede contener letras, espacios, guiones y apostrofes.',
            'postal_code.regex' => 'El código postal debe tener exactamente 5 dígitos.',
            'registered_at.before_or_equal' => 'La fecha de registro no puede ser futura.',
        ]);

        $client = new Client();
        $client->DNI = strtoupper($request->DNI);
        $client->name = ucwords(strtolower($request->name));
        $client->email = strtolower($request->email);
        $client->telephone = $this->formatInternationalPhone($request->telephone);
        $client->city = $request->city ? ucwords(strtolower($request->city)) : null;
        $client->postal_code = $request->postal_code;
        $client->registered_at = $request->registered_at;
        $client->country = $request->country;
        $client->save();

        return Redirect::route('clients.index');
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'DNI' => [
                'required',
                'string',
                'unique:clients,DNI,' . $client->id,
            ],
            'name' => 'required|string|min:2|max:255|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/',
            'email' => 'required|email|unique:clients,email,' . $client->id . '|max:255',
            'telephone' => [
                'required',
                'string',
                'regex:/^(\+\d{1,4})\s*\d{6,15}$/',
                function ($attribute, $value, $fail) {
                    if (!$this->validateInternationalPhone($value)) {
                        $fail($this->getPhoneValidationError($value));
                    }
                },
            ],
            'city' => 'nullable|string|min:2|max:100|regex:/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-\']+$/',
            'postal_code' => 'nullable|string|regex:/^[0-9]{5}$/',
            'registered_at' => 'required|date|before_or_equal:today',
        ], [
            'country.required' => 'Debes seleccionar un país.',
            'country.in'       => 'País no válido.',
            'DNI.unique'       => 'Este documento ya está registrado.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'telephone.regex' => 'El formato del teléfono no es válido.',
            'city.regex' => 'La ciudad solo puede contener letras, espacios, guiones y apostrofes.',
            'postal_code.regex' => 'El código postal debe tener exactamente 5 dígitos.',
            'registered_at.before_or_equal' => 'La fecha de registro no puede ser futura.',
        ]);

        $client->DNI = strtoupper($request->DNI);
        $client->name = ucwords(strtolower($request->name));
        $client->email = strtolower($request->email);
        $client->telephone = $this->formatInternationalPhone($request->telephone);
        $client->city = $request->city ? ucwords(strtolower($request->city)) : null;
        $client->postal_code = $request->postal_code;
        $client->registered_at = $request->registered_at;
        $client->country = $request->country;
        $client->save();

        return Redirect::route('clients.index');
    }

    private function validateSpanishDNI($dni)
    {
        $dni = strtoupper($dni);
        if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
            return false;
        }

        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $number = intval(substr($dni, 0, 8));
        $letter = substr($dni, 8, 1);

        return $letters[$number % 23] === $letter;
    }

    private function validatePortugueseNIF($nif)
    {
        $nif = preg_replace('/\D/', '', $nif);
        if (!preg_match('/^\d{9}$/', $nif)) {
            return false;
        }
        $digits = array_map('intval', str_split($nif));
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += $digits[$i] * (9 - $i);
        }
        $remainder = $sum % 11;
        $check = ($remainder < 2) ? 0 : 11 - $remainder;
        return $check === $digits[8];
    }

    private function formatInternationalPhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^\+\d{1,4}\s\d+$/', $phone)) {
            return $phone;
        }
        
        if (preg_match('/^(\+\d{1,4})(\d+)$/', $phone, $matches)) {
            $countryCode = $matches[1];
            $number = $matches[2];
            
            $isValid = false;
            switch ($countryCode) {
                case '+34':
                    $isValid = preg_match('/^[6-9]\d{8}$/', $number) && strlen($number) === 9;
                    break;
                case '+351':
                    $isValid = preg_match('/^9[1236]\d{7}$/', $number) && strlen($number) === 9;
                    break;
                case '+376':
                    $isValid = preg_match('/^[36]\d{5}$/', $number) && strlen($number) === 6;
                    break;
                case '+33':
                    $isValid = preg_match('/^[67]\d{8}$/', $number) && strlen($number) === 9;
                    break;
                case '+39':
                    $isValid = preg_match('/^3\d{9}$/', $number) && strlen($number) === 10;
                    break;
                case '+49':
                    $isValid = preg_match('/^1[567]\d{8}$/', $number) && strlen($number) === 10;
                    break;
                case '+44':
                    $isValid = preg_match('/^7[1-9]\d{8}$/', $number) && strlen($number) === 10;
                    break;
                case '+1':
                    $isValid = preg_match('/^[2-9]\d{2}[2-9]\d{6}$/', $number) && strlen($number) === 10;
                    break;
                case '+52':
                    $isValid = preg_match('/^[1-9]\d{9}$/', $number) && strlen($number) === 10;
                    break;
                case '+54':
                    $isValid = preg_match('/^9\d{8,10}$/', $number) && (strlen($number) >= 9 && strlen($number) <= 11);
                    break;
                case '+57':
                    $isValid = preg_match('/^3\d{9}$/', $number) && strlen($number) === 10;
                    break;
                case '+56':
                    $isValid = preg_match('/^[89]\d{7,8}$/', $number) && (strlen($number) === 8 || strlen($number) === 9);
                    break;
                case '+51':
                    $isValid = preg_match('/^9\d{8}$/', $number) && strlen($number) === 9;
                    break;
                case '+593':
                    $isValid = preg_match('/^[89]\d{7}$/', $number) && strlen($number) === 8;
                    break;
                case '+58':
                    $isValid = preg_match('/^4\d{9}$/', $number) && strlen($number) === 10;
                    break;
                case '+591':
                    $isValid = preg_match('/^[67]\d{7}$/', $number) && strlen($number) === 8;
                    break;
                default:
                    $isValid = strlen($number) >= 6 && strlen($number) <= 15;
            }
            
            if ($isValid) {
                return $countryCode . ' ' . $number;
            }
        }
        
        if (!str_starts_with($phone, '+')) {
            if (preg_match('/^[6-9]\d{8}$/', $phone) && strlen($phone) === 9) {
                return '+34 ' . $phone;
            }
        }
        
        return $phone;
    }

    private function validateInternationalPhone($phone)
    {
        if (!preg_match('/^(\+\d{1,4})\s*\d{6,15}$/', $phone)) {
            return false;
        }
        
        if (preg_match('/^(\+\d{1,4})\s*(\d+)$/', $phone, $matches)) {
            $countryCode = $matches[1];
            $number = $matches[2];
            
            switch ($countryCode) {
                case '+34':
                    return preg_match('/^[6-9]\d{8}$/', $number) && strlen($number) === 9;
                case '+351':
                    return preg_match('/^9[1236]\d{7}$/', $number) && strlen($number) === 9;
                case '+376':
                    return preg_match('/^[36]\d{5}$/', $number) && strlen($number) === 6;
                case '+33':
                    return preg_match('/^[67]\d{8}$/', $number) && strlen($number) === 9;
                case '+39':
                    return preg_match('/^3\d{9}$/', $number) && strlen($number) === 10;
                case '+49':
                    return preg_match('/^1[567]\d{8}$/', $number) && strlen($number) === 10;
                case '+44':
                    return preg_match('/^7[1-9]\d{8}$/', $number) && strlen($number) === 10;
                case '+52':
                    return preg_match('/^[1-9]\d{9}$/', $number) && strlen($number) === 10;
                case '+54':
                    return preg_match('/^9\d{8,10}$/', $number) && (strlen($number) >= 9 && strlen($number) <= 11);
                case '+57':
                    return preg_match('/^3\d{9}$/', $number) && strlen($number) === 10;
                case '+56':
                    return preg_match('/^[89]\d{7,8}$/', $number) && (strlen($number) === 8 || strlen($number) === 9);
                case '+51': 
                    return preg_match('/^9\d{8}$/', $number) && strlen($number) === 9;
                case '+593':
                    return preg_match('/^[89]\d{7}$/', $number) && strlen($number) === 8;
                case '+58':
                    return preg_match('/^4\d{9}$/', $number) && strlen($number) === 10;
                case '+591':
                    return preg_match('/^[67]\d{7}$/', $number) && strlen($number) === 8;
                default:
                    return strlen($number) >= 6 && strlen($number) <= 15;
            }
        }
        
        return false;
    }

    private function getPhoneValidationError($phone)
    {
        if (preg_match('/^(\+\d{1,4})\s*(\d+)$/', $phone, $matches)) {
            $countryCode = $matches[1];
            $number = $matches[2];
            
            switch ($countryCode) {
                case '+34':
                    return 'El número debe empezar por 6-9 y tener 9 dígitos';
                case '+351':
                    return 'El número debe empezar por 91, 92, 93 o 96 y tener 9 dígitos';
                case '+33':
                    return 'El número debe empezar por 6 o 7 y tener 9 dígitos';
                case '+39':
                    return 'El número debe empezar por 3 y tener 10 dígitos';
                case '+49':
                    return 'El número debe empezar por 15, 16 o 17 y tener 10 dígitos';
                case '+44':
                    return 'El número debe empezar por 7 y tener 10 dígitos';
                case '+52':
                    return 'El número debe empezar por 1-9 y tener 10 dígitos';
                case '+54':
                    return 'El número debe empezar por 9 y tener entre 9-11 dígitos';
                case '+57':
                    return 'El número debe empezar por 3 y tener 10 dígitos';
                case '+56':
                    return 'El número debe empezar por 8 o 9 y tener 8-9 dígitos';
                case '+51':
                    return 'El número debe empezar por 9 y tener 9 dígitos';
                case '+593':
                    return 'El número debe empezar por 8 o 9 y tener 8 dígitos';
                case '+58':
                    return 'El número debe empezar por 4 y tener 10 dígitos';
                case '+591':
                    return 'El número debe empezar por 6 o 7 y tener 8 dígitos';
                default:
                    return 'El número de teléfono no es válido para el país seleccionado.';
            }
        }
        
        return 'El formato del teléfono no es válido.';
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();
            return Redirect::route('clients.index');
        } catch (\Exception $e) {
            return Redirect::route('clients.index')->with('error', 'No se pudo eliminar el cliente. Puede tener vehículos asociados.');
        }
    }
}