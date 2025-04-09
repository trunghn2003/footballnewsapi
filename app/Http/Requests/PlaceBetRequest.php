<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\BalanceService;

class PlaceBetRequest extends FormRequest
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fixture_id' => 'required|integer',
            'bet_type' => 'required|string|in:WIN,DRAW,LOSS,SCORE',
            'amount' => [
                'required',
                'numeric',
                'min:10000', // Tối thiểu 10,000đ
                'max:10000000000', // Tối đa 10,000,000,000 đ
                function ($attribute, $value, $fail) {
                    // Kiểm tra số dư
                    $balance = $this->balanceService->getBalance($this->user());
                    if ($balance['balance'] < $value) {
                        $fail('Số dư không đủ để đặt cược.');
                    }
                }
            ],
            'predicted_score' => 'required_if:bet_type,SCORE|array',
            'predicted_score.home' => 'required_if:bet_type,SCORE|integer|min:0',
            'predicted_score.away' => 'required_if:bet_type,SCORE|integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'fixture_id.required' => 'Vui lòng chọn trận đấu.',
            'bet_type.required' => 'Vui lòng chọn loại cược.',
            'bet_type.in' => 'Loại cược không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số tiền cược.',
            'amount.numeric' => 'Số tiền cược phải là số.',
            'amount.min' => 'Số tiền cược tối thiểu là 10,000đ.',
            'amount.max' => 'Số tiền cược tối đa là 10,000,000đ.',
            'predicted_score.required_if' => 'Vui lòng nhập tỷ số dự đoán cho cược tỷ số.',
            'predicted_score.array' => 'Tỷ số dự đoán không hợp lệ.',
            'predicted_score.home.required_if' => 'Vui lòng nhập số bàn thắng của đội nhà.',
            'predicted_score.away.required_if' => 'Vui lòng nhập số bàn thắng của đội khách.',
            'predicted_score.home.integer' => 'Số bàn thắng của đội nhà phải là số nguyên.',
            'predicted_score.away.integer' => 'Số bàn thắng của đội khách phải là số nguyên.',
            'predicted_score.home.min' => 'Số bàn thắng của đội nhà không thể âm.',
            'predicted_score.away.min' => 'Số bàn thắng của đội khách không thể âm.'
        ];
    }
}
