import re

with open('resources/views/purchases/form.blade.php', 'r') as f:
    content = f.read()

# 1. Update title and title text
content = content.replace("@section('title','Receive purchase')", "@section('title', isset($purchase) ? 'Edit purchase' : 'Receive purchase')")
content = content.replace("<h1 class=\"mt-2 font-serif text-3xl text-ink\">Receive a purchase</h1>", "<h1 class=\"mt-2 font-serif text-3xl text-ink\">{{ isset($purchase) ? 'Edit purchase' : 'Receive a purchase' }}</h1>")

# 2. Update form action and Alpine data
content = content.replace("action=\"{{ route('purchases.store') }}\" x-data='purchaseForm(@json($catalog), @json($methods), @json($chequesList))'>@csrf<section", "action=\"{{ isset($purchase) ? route('purchases.update', $purchase) : route('purchases.store') }}\" x-data='purchaseForm(@json($catalog), @json($methods), @json($chequesList), @json(isset($purchase) ? $purchase : null))'>@csrf @if(isset($purchase)) @method('PUT') @endif <section")

# 3. Update inputs
content = content.replace("name=\"store_id\" required>@foreach($stores as $s)<option value=\"{{ $s->id }}\"", "name=\"store_id\" required>@foreach($stores as $s)<option value=\"{{ $s->id }}\" {{ (isset($purchase) && $purchase->store_id == $s->id) ? 'selected' : '' }}")
content = content.replace("name=\"supplier_invoice_no\">", "name=\"supplier_invoice_no\" value=\"{{ $purchase->supplier_invoice_no ?? '' }}\">")
content = content.replace("name=\"reference_no\">", "name=\"reference_no\" value=\"{{ $purchase->reference_no ?? '' }}\">")
content = content.replace("name=\"purchase_date\" value=\"{{ now()->toDateString() }}\"", "name=\"purchase_date\" value=\"{{ isset($purchase) ? $purchase->purchase_date->toDateString() : now()->toDateString() }}\"")
content = content.replace("name=\"due_date\">", "name=\"due_date\" value=\"{{ isset($purchase) && $purchase->due_date ? $purchase->due_date->toDateString() : '' }}\">")
content = content.replace("name=\"notes\">", "name=\"notes\" value=\"{{ $purchase->notes ?? '' }}\">")

# 4. Wrap payments section
payments_section = '<section class="card mt-5 overflow-hidden"><div class="flex items-center justify-between p-5"><div><h2 class="font-serif text-xl">Payments (Optional)</h2>'
if payments_section in content:
    content = content.replace(payments_section, '@if(!isset($purchase))' + payments_section)

# End if for payments section
cancel_btn = '<div class="mt-6 flex justify-end gap-3"><a class="btn-soft" href="{{ route(\'purchases.index\') }}">Cancel</a>'
if cancel_btn in content:
    content = content.replace(cancel_btn, '@endif' + cancel_btn)

# Update submit button text
content = content.replace("<button class=\"btn-teal\">Receive & update stock</button>", "<button class=\"btn-teal\">{{ isset($purchase) ? 'Update Purchase' : 'Receive & update stock' }}</button>")

# 5. Update Javascript
js_old = "function purchaseForm(products, methods, chequesList) {\n    return {\n        products, methods, chequesList, extra: 0, supplier: '', rows: [{ product_id: '', unit_id: '', quantity: 1, cost: 0, system_cost: 0, discount: 0 }], payments: [],"
js_new = """function purchaseForm(products, methods, chequesList, editPurchase = null) {
    let initialRows = [{ product_id: '', unit_id: '', quantity: 1, cost: 0, system_cost: 0, discount: 0 }];
    let initialSupplier = '';
    let initialExtra = 0;
    
    if (editPurchase) {
        initialSupplier = editPurchase.supplier_id;
        initialExtra = parseFloat(editPurchase.extra_cost_total) || 0;
        initialRows = editPurchase.items.map(item => ({
            product_id: item.product_id,
            unit_id: item.unit_id,
            quantity: item.quantity,
            cost: item.supplier_unit_cost,
            system_cost: item.system_unit_cost,
            discount: item.discount_amount || 0
        }));
    }

    return {
        products, methods, chequesList, extra: initialExtra, supplier: initialSupplier, rows: initialRows, payments: [],"""

content = content.replace(js_old, js_new)

with open('resources/views/purchases/form.blade.php', 'w') as f:
    f.write(content)

print("Patched form.blade.php successfully!")
