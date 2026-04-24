<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::withCount('consultations')
            ->with('admins');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('account_id')) {
            $query->where('id', $request->account_id);
        }

        if ($request->filled('category')) {
            $query->where('description', $request->category);
        }

        $accounts = $query->orderBy('name')->paginate(15)->appends($request->query());

        $categories = Account::select('description')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->distinct()
            ->pluck('description');

        return view('accounts.index', compact('accounts', 'categories'));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(AccountRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('accounts', 'public');
        }

        Account::create($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Akun interior baru berhasil ditambahkan!');
    }

    public function edit(Account $account)
    {
        $admins = User::where('role', UserRole::Admin)
            ->where(function($q) use ($account) {
                $q->whereNull('account_id')
                  ->orWhere('account_id', $account->id);
            })
            ->get();
        return view('accounts.edit', compact('account', 'admins'));
    }

    public function update(AccountRequest $request, Account $account)
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            if ($account->logo_path) {
                Storage::disk('public')->delete($account->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('accounts', 'public');
        }

        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Data akun berhasil diperbarui!');
    }

    public function destroy(Account $account)
    {
        DB::transaction(function () use ($account) {
            User::where('role', UserRole::Admin)
                ->where('account_id', $account->id)
                ->update(['account_id' => null]);

            if ($account->logo_path) {
                Storage::disk('public')->delete($account->logo_path);
            }

            $account->delete();
        });

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil dihapus. Seluruh lead terkait ikut terhapus melalui cascade database.');
    }
}
