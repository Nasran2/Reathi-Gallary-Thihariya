<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6" x-data="reportFilters()">
    <form method="GET" id="filter-form">
        <!-- Preserve existing query params if needed (like export) -->
        @foreach(request()->except(['from', 'to', 'export', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}" id="hidden-{{ $key }}">
        @endforeach
        
        <div class="flex flex-wrap gap-2 mb-5 pb-5 border-b border-slate-100 bg-slate-50 p-2 rounded-xl">
            <button type="button" @click="setDate('all')" :class="range === 'all' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">All Time</button>
            <button type="button" @click="setDate('today')" :class="range === 'today' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">Today</button>
            <button type="button" @click="setDate('yesterday')" :class="range === 'yesterday' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">Yesterday</button>
            <button type="button" @click="setDate('this_week')" :class="range === 'this_week' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">This Week</button>
            <button type="button" @click="setDate('last_week')" :class="range === 'last_week' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">Last Week</button>
            <button type="button" @click="setDate('this_month')" :class="range === 'this_month' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">This Month</button>
            <button type="button" @click="setDate('last_month')" :class="range === 'last_month' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">Last Month</button>
            <button type="button" @click="setDate('this_year')" :class="range === 'this_year' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">This Year</button>
            <button type="button" :class="range === 'custom' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm transition">Custom</button>
        </div>
        
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="from" x-model="from" @change="range = 'custom'" class="rounded-xl border-slate-200 py-2.5 px-3 w-40">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="to" x-model="to" @change="range = 'custom'" class="rounded-xl border-slate-200 py-2.5 px-3 w-40">
            </div>
            
            {{ $slot ?? '' }}
            
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold px-6 py-2.5 rounded-xl shadow-sm transition">Apply</button>
            <a href="{{ url()->current() }}" class="bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold px-6 py-2.5 rounded-xl transition">Reset</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportFilters', () => ({
        from: '{{ request('from', date('Y-m-d')) }}',
        to: '{{ request('to', date('Y-m-d')) }}',
        range: 'custom',
        
        init() {
            let today = this.formatDate(new Date());
            if (!'{{ request('from') }}' && !'{{ request('to') }}') {
                this.range = 'today';
                this.from = today;
                this.to = today;
            } else if (!this.from && !this.to) {
                this.range = 'all';
            }
        },
        
        setDate(type) {
            this.range = type;
            let d = new Date();
            let d2 = new Date();
            
            if (type === 'today') {
                this.from = this.formatDate(d);
                this.to = this.formatDate(d);
            } else if (type === 'yesterday') {
                d.setDate(d.getDate() - 1);
                this.from = this.formatDate(d);
                this.to = this.formatDate(d);
            } else if (type === 'this_week') {
                d.setDate(d.getDate() - d.getDay() + (d.getDay() === 0 ? -6 : 1));
                this.from = this.formatDate(d);
                this.to = this.formatDate(new Date());
            } else if (type === 'last_week') {
                d.setDate(d.getDate() - d.getDay() - 6);
                d2.setDate(d2.getDate() - d2.getDay());
                this.from = this.formatDate(d);
                this.to = this.formatDate(d2);
            } else if (type === 'this_month') {
                d.setDate(1);
                this.from = this.formatDate(d);
                this.to = this.formatDate(new Date());
            } else if (type === 'last_month') {
                d.setMonth(d.getMonth() - 1);
                d.setDate(1);
                d2.setDate(0);
                this.from = this.formatDate(d);
                this.to = this.formatDate(d2);
            } else if (type === 'this_year') {
                d.setMonth(0);
                d.setDate(1);
                this.from = this.formatDate(d);
                this.to = this.formatDate(new Date());
            } else if (type === 'all') {
                this.from = '';
                this.to = '';
            }
            
            setTimeout(() => {
                // Clear any hidden fields from the form before submitting except what's needed
                document.getElementById('filter-form').submit();
            }, 50);
        },
        
        formatDate(date) {
            let d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-');
        }
    }));
});
</script>
