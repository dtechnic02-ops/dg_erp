<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class SalesInvoice extends Model

{

    use HasFactory;



    protected $fillable = [



        'created_by',

        'company_id',

        'financial_year_id',

        'customer_id',



        'invoice_no',



        'sale_date',

        'due_date',



        'subtotal',

        'discount',

        'total_vat',

        'grand_total',



        'paid_amount',

        'due_amount',



        'payment_status',



        'note',



        'status',



    ];



    protected $casts = [

        'sale_date'      => 'date',

        'due_date'       => 'date',

        'subtotal'       => 'decimal:2',

        'discount'       => 'decimal:2',

        'total_vat'      => 'decimal:2',

        'grand_total'    => 'decimal:2',

        'paid_amount'    => 'decimal:2',

        'due_amount'     => 'decimal:2',

        'status'         => 'integer',

    ];



    public function customer()

    {

        return $this->belongsTo(Customer::class);

    }



    public function items()

    {

        return $this->hasMany(

            SalesItem::class,

            'sales_invoice_id'

        );

    }



    public function company()

    {

        return $this->belongsTo(

            Company::class,

            'company_id'

        );

    }



    public function creator()

    {

        return $this->belongsTo(

            User::class,

            'created_by'

        );

    }



    public function financialYear()

    {

        return $this->belongsTo(

            FinancialYear::class

        );

    }



    public function payments()

    {

        return $this->hasMany(

            SalesPayment::class,

            'sales_invoice_id'

        );

    }



    public function activePayments()

    {

        return $this->hasMany(

            SalesPayment::class,

            'sales_invoice_id'

        )->where('status', SalesPayment::STATUS_ACTIVE);

    }



    public function sumActivePaidAmount(): float

    {

        return round((float) $this->activePayments()->sum('paid_amount'), 2);

    }



    public function dueDaysLabel(): string

    {

        if ((float) $this->due_amount <= 0) {

            return 'Paid';

        }



        if (!$this->due_date) {

            return '-';

        }



        $today = \Illuminate\Support\Carbon::today();

        $dueDate = \Illuminate\Support\Carbon::parse($this->due_date)->startOfDay();



        if ($today->lt($dueDate)) {

            return $today->diffInDays($dueDate) . ' Days Left';

        }



        if ($today->gt($dueDate)) {

            return 'Overdue ' . $dueDate->diffInDays($today) . ' Days';

        }



        return 'Due Today';

    }

}


