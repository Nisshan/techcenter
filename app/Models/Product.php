<?php

namespace App\Models;

use Freshbitsweb\LaravelCartManager\Traits\Cartable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    use Cartable;

    protected $guarded = [];


    protected $casts = [
        'created_at' => 'date:Y-M-d',
        'updated_at' => 'date:Y-M-d',
    ];


    protected static function booted()
    {
        Product::creating(function ($model) {
            if (auth()->user()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = ucwords($name);
        $this->attributes['slug'] = $this->slug($name);
    }

    public function getPrice()
    {
        if ($this->has_offer == 1) {
            if ($this->offer_type == 1) {
                return $this->price * $this->percent_off / 100;
            }
            return $this->price - $this->amount_off;
        }
        return $this->price;


    }

    public function getImage()
    {
        return $this->cover ? url('storage/thumb/' . $this->cover) : '/images/logo.png';
    }


    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImages::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot('quantity');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    private function slug($name)
    {
        $slug = Str::slug($name);
        $count = Product::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        if (request()->method() == 'PATCH') {
            return $this->slug == $slug ? $slug : "{$slug}-{$count}";
        }

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function getCoverImage()
    {
        return url('storage/images/' . $this->cover);
    }

    //this is for the frontend path as it will include a default image to show
    public function getPath()
    {
        return $this->cover ? url('storage/images/' . $this->cover) : '/images/logo.png';
    }

    public function getCoverThumb()
    {
        return url('storage/thumb/' . $this->cover);
    }


}
