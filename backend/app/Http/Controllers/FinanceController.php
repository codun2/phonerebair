<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** المالية: ملخص الفترة + المدفوعات + المصروفات + الديون */
class FinanceController extends Controller
{
    public function summary(Request $request)
    {
        [$from, $to] = $this->range($request);

        $payments = Payment::with(['ticket:id,ticket_no,customer_name', 'user:id,name'])
            ->whereBetween('paid_at', [$from, $to->copy()->endOfDay()])
            ->orderByDesc('paid_at')->get();

        $revenue = (float) $payments->sum('amount');
        $byMethod = $payments->groupBy('method')->map(fn ($g) => (float) $g->sum('amount'));

        $expenses = (float) Expense::whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])->sum('amount');

        // تكلفة القطع المستخدمة في الفترة (snapshot وقت الاستخدام)
        $partsCost = (float) TicketPart::whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->sum(DB::raw('qty * unit_cost'));

        // الديون الكلية: تذاكر عليها مبالغ غير محصّلة (ليست مرتبطة بالفترة)
        $debtRows = Ticket::whereNotIn('status', ['cancelled', 'rejected', 'unrepairable'])
            ->whereNotNull('price')
            ->withSum('payments as paid_sum', 'amount')
            ->get()
            ->filter(fn ($t) => (float) $t->price - (float) ($t->paid_sum ?? 0) > 0.009);

        $inventoryValue = (float) InventoryItem::sum(DB::raw('qty * COALESCE(cost, 0)'));

        return response()->json([
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'cards' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'parts_cost' => $partsCost,
                'net' => round($revenue - $expenses - $partsCost, 2),
                'debts' => round((float) $debtRows->sum(fn ($t) => (float) $t->price - (float) ($t->paid_sum ?? 0)), 2),
                'inventory_value' => $inventoryValue,
            ],
            'by_method' => Payment::METHODS,
            'method_labels' => Payment::METHOD_LABELS,
            'method_totals' => $byMethod,
            'payments' => $payments->map(fn ($p) => [
                'id' => $p->id, 'amount' => (float) $p->amount, 'method' => $p->method,
                'method_label' => Payment::METHOD_LABELS[$p->method] ?? $p->method,
                'ticket_id' => $p->ticket_id, 'ticket_no' => $p->ticket?->ticket_no,
                'customer' => $p->ticket?->customer_name, 'note' => $p->note,
                'user' => $p->user?->name, 'paid_at' => $p->paid_at->format('Y-m-d H:i'),
            ])->values(),
            'debts' => $debtRows->sortByDesc(fn ($t) => (float) $t->price - (float) ($t->paid_sum ?? 0))
                ->take(100)->values()->map(fn ($t) => [
                    'ticket_id' => $t->id, 'ticket_no' => $t->ticket_no,
                    'customer_name' => $t->customer_name, 'customer_phone' => $t->customer_phone,
                    'customer_id' => $t->customer_id,
                    'status' => $t->status, 'status_label' => Ticket::STATUS_LABELS[$t->status] ?? $t->status,
                    'price' => (float) $t->price, 'paid' => (float) ($t->paid_sum ?? 0),
                    'balance' => round((float) $t->price - (float) ($t->paid_sum ?? 0), 2),
                    'created_at' => $t->created_at->format('Y-m-d'),
                ]),
        ]);
    }

    public function expenses(Request $request)
    {
        [$from, $to] = $this->range($request);

        return response()->json(
            Expense::with('user:id,name')
                ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
                ->orderByDesc('spent_at')->get()
        );
    }

    public function storeExpense(Request $request)
    {
        $data = $this->validatedExpense($request);

        return response()->json(Expense::create($data + ['user_id' => $request->user()->id]), 201);
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $expense->update($this->validatedExpense($request));

        return response()->json(['message' => 'تم التحديث', 'expense' => $expense]);
    }

    public function destroyExpense(Expense $expense)
    {
        $expense->delete();

        return response()->json(['message' => 'تم حذف المصروف']);
    }

    private function validatedExpense(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'nullable|string|max:60',
            'amount' => 'required|numeric|min:0.01',
            'spent_at' => 'required|date',
            'note' => 'nullable|string|max:300',
        ]);
    }

    private function range(Request $request): array
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        return [$from->copy()->startOfDay(), $to];
    }
}
