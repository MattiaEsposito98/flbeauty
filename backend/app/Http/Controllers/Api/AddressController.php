<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ResolvesComuneForAddress;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ResolvesComuneForAddress;

    public function index(Request $request)
    {
        return $request->user()->addresses()->with('comune')->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $address = $request->user()->addresses()->create($data);

        return response()->json($address->load('comune'), 201);
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeOwnership($request, $address);

        $data = $this->validated($request);

        $address->update($data);

        return $address->load('comune');
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorizeOwnership($request, $address);

        $address->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:255'],
            'comune_id' => ['required', 'integer', 'exists:comuni,id'],
            'postal_code' => ['required', 'string', 'size:5'],
            'is_default' => ['boolean'],
        ]);

        $comuneFields = $this->resolveComuneFields($data['comune_id'], $data['postal_code']);

        return [
            ...$data,
            'postal_code' => $comuneFields['postal_code'],
            'province' => $comuneFields['province'],
        ];
    }

    private function authorizeOwnership(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }
}
