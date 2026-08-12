<div class="grid gap-5 lg:grid-cols-3">
    <div class="card p-6 bg-white border border-slate-200 rounded-xl shadow-sm">
        <span class="inline-block px-3 py-1 bg-teal-50 text-teal-700 text-xs font-bold uppercase rounded-full tracking-wider mb-2">MAIN INVENTORY</span>
        <h2 class="font-serif text-2xl text-slate-800 mb-5">Main Sales Profit</h2>
        <div class="space-y-4">
            <div class="flex justify-between items-center text-slate-600">
                <span>Net sales</span>
                <strong class="text-slate-800 text-lg">Rs. {{ number_format($main->sales, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center text-slate-600 border-b border-slate-100 pb-4">
                <span>Cost of Goods (COGS)</span>
                <strong class="text-slate-800 text-lg">Rs. {{ number_format($main->cogs, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="font-bold text-slate-800">Gross profit</span>
                <strong class="text-xl {{ $main->profit < 0 ? 'text-red-600' : 'text-emerald-600' }}">Rs. {{ number_format($main->profit, 2) }}</strong>
            </div>
        </div>
    </div>
    
    <div class="card p-6 bg-white border border-slate-200 rounded-xl shadow-sm">
        <span class="inline-block px-3 py-1 bg-amber-50 text-amber-700 text-xs font-bold uppercase rounded-full tracking-wider mb-2">REMNANT</span>
        <h2 class="font-serif text-2xl text-slate-800 mb-5">Remnant Profit</h2>
        <div class="space-y-4">
            <div class="flex justify-between items-center text-slate-600">
                <span>Net sales</span>
                <strong class="text-slate-800 text-lg">Rs. {{ number_format($remnant->sales, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center text-slate-600 border-b border-slate-100 pb-4">
                <span>Cost of Goods (COGS)</span>
                <strong class="text-slate-800 text-lg">Rs. {{ number_format($remnant->cogs, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center pt-2">
                <span class="font-bold text-slate-800">Gross profit</span>
                <strong class="text-xl {{ $remnant->profit < 0 ? 'text-red-600' : 'text-emerald-600' }}">Rs. {{ number_format($remnant->profit, 2) }}</strong>
            </div>
        </div>
    </div>
    
    <div class="card bg-slate-900 border border-slate-800 p-6 rounded-xl shadow-xl text-white">
        <span class="inline-block px-3 py-1 bg-white/10 text-slate-200 text-xs font-bold uppercase rounded-full tracking-wider mb-2">COMBINED BUSINESS</span>
        <h2 class="font-serif text-2xl text-white mb-5">Net Result</h2>
        <div class="space-y-4">
            <div class="flex justify-between items-center text-slate-300">
                <span>Total Gross Profit</span>
                <strong class="text-white text-lg">Rs. {{ number_format($main->profit + $remnant->profit, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center text-rose-300 border-b border-white/10 pb-4">
                <span>Total Expenses</span>
                <strong class="text-lg">− Rs. {{ number_format($expenses, 2) }}</strong>
            </div>
            <div class="flex justify-between items-center pt-2 text-xl">
                <span class="font-bold text-white">Net Profit / Loss</span>
                <strong class="text-2xl {{ ($main->profit + $remnant->profit - $expenses) < 0 ? 'text-red-400' : 'text-emerald-400' }}">
                    Rs. {{ number_format($main->profit + $remnant->profit - $expenses, 2) }}
                </strong>
            </div>
        </div>
    </div>
</div>
