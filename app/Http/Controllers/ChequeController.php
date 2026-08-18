<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\ChequeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    public function dashboard()
    {
        $base = Cheque::query();
        $summary = ['received' => (clone $base)->where('direction', 'received')->whereIn('status', ['pending', 'deposited', 'endorsed'])->count(), 'issued' => (clone $base)->where('direction', 'issued')->whereIn('status', ['pending', 'deposited', 'endorsed'])->count(), 'today' => (clone $base)->whereIn('status', ['pending', 'deposited', 'endorsed'])->whereDate('cheque_date', today())->count(), 'next_two' => (clone $base)->whereIn('status', ['pending', 'deposited', 'endorsed'])->whereBetween('cheque_date', [today(), today()->addDays(2)])->count(), 'returned' => (clone $base)->where('status', 'returned')->count(), 'pending_value' => (clone $base)->whereIn('status', ['pending', 'deposited', 'endorsed'])->sum('amount')];
        $upcoming = Cheque::with('customer', 'supplier')->whereIn('status', ['pending', 'deposited', 'endorsed'])->whereDate('cheque_date', '<=', today()->addDays(2))->orderBy('cheque_date')->get();

        return view('cheques.dashboard', compact('summary', 'upcoming'));
    }

    public function index(Request $r, ?string $direction = null)
    {
        $query = Cheque::with('customer', 'supplier', 'endorsements.supplier')
            ->when($direction, fn ($q) => $q->where('direction', $direction))
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->bank, fn ($q, $v) => $q->where('bank', 'like', "%{$v}%"))
            ->when($r->customer_id, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($r->supplier_id, fn ($q, $v) => $q->where(fn ($x) => $x->where('supplier_id', $v)->orWhereHas('endorsements', fn ($e) => $e->where('supplier_id', $v))))
            ->when($r->from, fn ($q, $v) => $q->whereDate('cheque_date', '>=', $v))
            ->when($r->to, fn ($q, $v) => $q->whereDate('cheque_date', '<=', $v))
            ->when($r->q, fn ($q, $v) => $q->where('cheque_number', 'like', "%{$v}%"));
        if ($r->type === 'endorsed') {
            $query->whereHas('endorsements');
        }
        if ($r->type === 'upcoming') {
            $query->whereIn('status', ['pending', 'deposited', 'endorsed'])->whereBetween('cheque_date', [today(), today()->addDays(2)]);
        }
        $all = (clone $query)->orderByDesc('cheque_date')->get();
        if ($r->export === 'csv') {
            return response()->streamDownload(function () use ($all) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Direction', 'Cheque No', 'Party', 'Bank', 'Amount', 'Cheque Date', 'Status']);
                foreach ($all as $c) {
                    fputcsv($out, [$c->direction, $c->cheque_number, $c->customer?->name ?? $c->supplier?->name, $c->bank, $c->amount, $c->cheque_date->format('Y-m-d'), $c->status]);
                } fclose($out);
            }, 'cheques-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
        }
        if ($r->export === 'pdf') {
            return Pdf::loadView('cheques.print', ['cheques' => $all])->setPaper('a4', 'landscape')->download('cheques-'.now()->format('Ymd').'.pdf');
        }
        if ($r->export === 'print') {
            return view('cheques.print', ['cheques' => $all]);
        }
        $cheques = $query->orderByDesc('cheque_date')->paginate(25)->withQueryString();

        return view('cheques.index', ['cheques' => $cheques, 'direction' => $direction, 'customers' => Customer::orderBy('name')->get(), 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function show(Cheque $cheque)
    {
        return view('cheques.show', ['cheque' => $cheque->load('customer', 'supplier', 'customerPayments.allocations.sale', 'supplierPayments.allocations.purchase', 'endorsements.supplier', 'events.user'), 'suppliers' => Supplier::where('active', true)->orderBy('name')->get()]);
    }

    public function deposit(Cheque $cheque, ChequeService $service, Request $r)
    {
        $service->deposit($cheque, $r->user()->id);

        return back()->with('success', 'Cheque marked as deposited.');
    }

    public function pass(Cheque $cheque, ChequeService $service, Request $r)
    {
        $service->pass($cheque, $r->user()->id);

        return back()->with('success', 'Cheque passed. All linked pending payments were cleared exactly once.');
    }

    public function returnCheque(Request $r, Cheque $cheque, ChequeService $service)
    {
        $data = $r->validate(['return_date' => 'required|date', 'return_reason' => 'nullable|max:255', 'bank_charge' => 'nullable|numeric|min:0', 'charge_customer' => 'nullable|boolean', 'notes' => 'nullable']);
        $service->returnCheque($cheque, $data, $r->user()->id);

        return back()->with('success', 'Cheque returned and all linked dues restored.');
    }

    public function endorse(Request $r, Cheque $cheque, ChequeService $service)
    {
        $data = $r->validate(['supplier_id' => 'required|exists:suppliers,id', 'amount' => 'required|numeric|gt:0', 'transfer_date' => 'required|date', 'allocation_mode' => 'required|in:automatic,manual', 'allocations' => 'nullable|array', 'allocations.*' => 'nullable|numeric|min:0', 'notes' => 'nullable']);
        $service->endorse($cheque, $data, $r->user()->id);

        return back()->with('success', 'Cheque endorsed to supplier as a linked pending payment.');
    }

    public function cancel(Request $r, Cheque $cheque, ChequeService $service)
    {
        $service->cancel($cheque, $r->user()->id);

        return back()->with('success', 'Pending cheque cancelled and allocations released.');
    }
}
