<?php

namespace Database\Factories;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'quantity' => rand(1,5),
            'status' => $this->faker->randomElement(['InReview','Pending','Canceled','Delivered'])
        ];
    }
}
