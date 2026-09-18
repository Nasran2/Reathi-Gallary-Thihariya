import re

with open('resources/views/suppliers/index.blade.php', 'r') as f:
    content = f.read()

# 1. Update x-data
old_xdata = '<div x-data="{add:false}" class="space-y-5">'
new_xdata = """<div x-data="{
    add: false,
    form: { id: '', name: '', company: '', phone: '', whatsapp: '', email: '', tax_number: '', opening_balance: 0, address: '', notes: '' },
    openEdit(s) {
        this.form = { ...s };
        this.add = true;
    },
    openAdd() {
        this.form = { id: '', name: '', company: '', phone: '', whatsapp: '', email: '', tax_number: '', opening_balance: 0, address: '', notes: '' };
        this.add = true;
    }
}" class="space-y-5">"""
content = content.replace(old_xdata, new_xdata)

# 2. Update Add button
old_add_btn = '<button class="btn-teal" @click="add=true">+ Add Supplier</button>'
new_add_btn = '<button class="btn-teal" @click="openAdd()">+ Add Supplier</button>'
content = content.replace(old_add_btn, new_add_btn)

# 3. Add Edit button in Actions
old_actions = '<div class="flex gap-1"><a class="btn-soft !px-2 !py-1" href="{{ route(\'suppliers.show\',$s) }}">View</a>'
new_actions = '<div class="flex gap-1"><a class="btn-soft !px-2 !py-1" href="{{ route(\'suppliers.show\',$s) }}">View</a>@can(\'suppliers.edit\')<button type="button" @click="openEdit({{ htmlspecialchars(json_encode($s)) }})" class="btn-soft !px-2 !py-1 text-teal-600">Edit</button>@endcan'
content = content.replace(old_actions, new_actions)

# 4. Update the modal form
old_form_start = '<form method="post" action="{{ route(\'suppliers.store\') }}" @click.outside="add=false" class="card w-full max-w-3xl p-6">@csrf<div class="mb-4 flex justify-between"><h2 class="font-serif text-2xl">Add Supplier</h2><button type="button" @click="add=false">✕</button></div>'
new_form_start = """<form method="post" :action="form.id ? '/suppliers/' + form.id : '{{ route('suppliers.store') }}'" @click.outside="add=false" class="card w-full max-w-3xl p-6">
@csrf
<template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
<div class="mb-4 flex justify-between">
    <h2 class="font-serif text-2xl" x-text="form.id ? 'Edit Supplier' : 'Add Supplier'"></h2>
    <button type="button" @click="add=false">✕</button>
</div>"""
content = content.replace(old_form_start, new_form_start)

# 5. Update inputs with x-model
replacements = {
    '<input class="w-full" name="name" required>': '<input class="w-full" name="name" x-model="form.name" required>',
    '<input class="w-full" name="company">': '<input class="w-full" name="company" x-model="form.company">',
    '<input class="w-full" name="phone">': '<input class="w-full" name="phone" x-model="form.phone">',
    '<input class="w-full" name="whatsapp">': '<input class="w-full" name="whatsapp" x-model="form.whatsapp">',
    '<input class="w-full" name="email" type="email">': '<input class="w-full" name="email" type="email" x-model="form.email">',
    '<input class="w-full" name="tax_number">': '<input class="w-full" name="tax_number" x-model="form.tax_number">',
    '<input class="w-full" name="opening_balance" type="number" step=".01" value="0">': '<input class="w-full" name="opening_balance" type="number" step=".01" x-model="form.opening_balance">',
    '<input class="w-full" name="address">': '<input class="w-full" name="address" x-model="form.address">',
    '<input class="w-full" name="notes">': '<input class="w-full" name="notes" x-model="form.notes">',
    '<button class="btn-teal">Save Supplier</button>': '<button class="btn-teal" x-text="form.id ? \'Update Supplier\' : \'Save Supplier\'"></button>'
}

for old, new in replacements.items():
    content = content.replace(old, new)

with open('resources/views/suppliers/index.blade.php', 'w') as f:
    f.write(content)

print("Patched suppliers index successfully!")
