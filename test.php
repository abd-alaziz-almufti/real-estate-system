<?php
\ = \App\Models\User::where('email', 'rent@app.com')->first();
\ = \->tenant;
dump('User: ' . (\ ? \->id : 'null') . ' Tenant: ' . (\ ? \->id : 'null'));
if (\) {
    \ = \->leases;
    dump('Leases: ' . \->count());
    dump('Payments Count: ' . \->payments()->count());
    dump('Payments Query: ' . \->payments()->toSql());
}

