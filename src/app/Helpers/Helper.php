<?php

namespace App\Helpers;

class Helper
{
    public static function getNameInitial(string $name): string
    {
        $words = explode(" ", $name);
        $firstWords = '';

        foreach ($words as $key => $word) {
            if ($key >= 2) {
                break;
            }

            $firstWords .= $word[0];
        }

        return strtoupper($firstWords);
    }

    public static function dayWeek($index)
    {
        $mapWeek = ['Doming', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        return $mapWeek[$index];
    }

    /**
     * Pegar o restante de horas de contrato no mês
     *
     * @param int $user
     * @param obj $store null / Ainda não aplicado
     * @return int
     */

    //

    public static function getRemainingContractHoursInMonth($user_id, $store_id = null, $date = null)
    {
        if ($date == null) {
            $date = Carbon::now();
        }

        $user = User::find($user_id);

        $contracts_id = ContractUser::where([
            'user_id' => $user->id
        ])->pluck('contract_id');

        if (!$contracts_id->count()) {
            return 0;
        }

        $contract = Contract::whereIn('id', $contracts_id)
            ->where('is_active', 1)
            ->where('status', 1)
            ->get()
            ->filter(function ($contract) use ($date) {
                return Carbon::parse($date)->between(Carbon::parse($contract->begin_date), Carbon::parse($contract->end_date)->addDay());
            })->reduce(function ($contract, $item) {
                return $item;
            });
        if (!$contract) {
            return 0;
        }

        $users = ContractUser::where([
            'contract_id' => $contract->id
        ])->pluck('user_id');

        $reservations = Reservation::where(['type' => 1, 'status' => 1])
            ->when($store_id != null, function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->where(function ($query) use ($date) {
                $query->whereMonth('booking_date', Carbon::parse($date)->format('m'))
                    ->whereYear('booking_date', Carbon::parse($date)->format('Y'));
            })
            ->whereIn('user_id', $users)
            ->count();

        $contract_month_hours = ContractMonthHours::where([
            'contract_id' => $contract->id,
            'month' => Carbon::parse($date)->format('m'),
            'year' => Carbon::parse($date)->format('Y')
        ])
            ->when($store_id != null, function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->sum('hours');
        return $contract_month_hours - $reservations;
    }

    public static function getcontractOfTheMonth($user, $date)
    {
        $contracts_id = ContractUser::where([
            'user_id' => $user->id
        ])->pluck('contract_id');

        if (!$contracts_id->count()) {
            return 0;
        }

        $contract = Contract::whereIn('id', $contracts_id)
            ->where('is_active', 1)
            ->where('status', 1)
            ->get()
            ->filter(function ($contract) use ($date) {
                return Carbon::parse($date)->between(Carbon::parse($contract->begin_date), Carbon::parse($contract->end_date)->addDay());
            })->reduce(function ($contract, $item) {
                return $item;
            });
        if (!$contract) {
            return false;
        }

        return $contract;
    }

    public static function getContractActive($user)
    {
        $contracts_id = ContractUser::where([
            'user_id' => $user->id
        ])->pluck('contract_id');

        if (!$contracts_id->count()) {
            return 0;
        }

        $contract = Contract::whereIn('id', $contracts_id)
            ->where('is_active', 1)
            ->where('status', 1)
            ->whereDate('begin_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now())
            ->first();

        return $contract;
    }
    public static function getmissingHoursReservation($reservation)
    {
        /**
         * @var Reservation $reservation
         */

        return Carbon::now()->diffInHours($reservation->booking_date->setTimeFrom($reservation->start_time)->format('Y-m-d H:i:s'), false);
    }

    /**
     * Verificar se a reserva esta disponível para edição ou cancelamento
     * @param obj $reservation
     * @return bool
     */
    public static function checkReservation($reservation)
    {
        if (Auth::user()->role == 0) {
            return true;
        }
        if ($reservation->status == 0 || !$reservation->is_active) {
            return false;
        }

        return Carbon::parse($reservation->booking_date . ' ' . $reservation->start_time)->subHours(24)->format('Y-m-d H:i') >= Carbon::now()->format('Y-m-d H:i');
    }

    /**
     * Coletar todos os estabelecimentos do contrato, juntamente com as horas que sobraram
     * @param int $id
     * return obj
     */
    public static function getContractStores($id)
    {
        $contract = Contract::with(['dependents', 'store', 'monthHours' => function ($query) {
            $query->orderBy('store_id', 'asc');
        }])->find($id);

        if (!$contract) {
            return [];
        }

        $users = ContractUser::where([
            'contract_id' => $contract->id
        ])->pluck('user_id');

        $stores = $contract->monthHours->map(function ($contract_month) use ($contract, $users) {
            $reservations_count =  Reservation::where([
                'type' => 1,
                'status' => 1,
                'store_id' => $contract_month->store_id
            ])
                ->whereMonth('booking_date', $contract_month->month)
                ->whereYear('booking_date', $contract_month->year)
                ->where(function ($query) use ($users) {
                    $query->whereIn('user_id', $users);
                })->count();
            return [
                'id' => $contract_month->id,
                'store_id' => $contract_month->store_id,
                'name' => Store::find($contract_month->store_id)->name,
                'is_matriz' => \App\Models\ContractStore::where(['contract_id' => $contract_month->contract_id, 'store_id' => $contract_month->store_id])->first()->is_matriz ? 1 : 0,
                'hour' => $contract_month->hours,
                'hours_used' => $reservations_count,
                'date' => $contract_month->month . '/' . $contract_month->year
            ];
        });

        return $stores;
    }

    /**
     * Verificar se o contrato está ativo
     */
    public static function verifyContractActive($contract_id)
    {
        $contract = Contract::find($contract_id);

        if (!$contract) {
            return false;
        }

        return $contract->end_date->addDay() > Carbon::now();
    }
    /**
     * Coletar todos as reservas do contrato
     * @param int $id
     * return obj
     */
    public static function getContractReservations($id)
    {
        $contract = Contract::with(['dependents', 'primary_user'])->find($id);

        if (!$contract) {
            return [];
        }

        $users = ContractUser::where(['contract_id' => $contract->id])->select('user_id')->get()->map(function ($item, $key) {
            return $item->user_id;
        });

        $reservations = Reservation::where(['type' => 1, 'status' => 1])
            ->whereBetween('booking_date', [$contract->sign_date, $contract->end_date])
            ->where(function ($query) use ($users) {
                $query->whereIn('user_id', [$users]);
            })->get();

        return $reservations;
    }

    public static function check_route($route_param)
    {

        if (gettype($route_param) == 'array') {
            foreach ($route_param as $item) {
                if (\Route::current()->getName() == $item) {
                    return 'active';
                }
            }
        } elseif (\Route::current()->getName() == $route_param) {
            return 'active';
        }

        return gettype($route_param);
    }

    public static function get_stores($id = false)
    {

        if ($id) {
            $stores = Store::find($id);
            $stores = $stores->name;
        } else {
            $stores = Store::all();
        }

        return $stores;
    }

    public static function user_role($role)
    {
        return config('role.user')[$role]['label'];
    }

    public static function get_how_about_us($index = false)
    {

        $items = [
            'Google',
            'Facebook',
            'Instagram',
            'E-mail',
            'Indicação',
            'Anúncio',
            'Eventos',
            'Outros'
        ];

        if ($index) {
            return $items[$index];
        }

        return $items;
    }

    public static function myCarbon($date)
    {
        return $date != '' ? Carbon::parse($date) : '-';
    }

    public static function parseMouth($number)
    {
        switch ($number) {
            case "01":
                $mes = "Jan";
                break;
            case "02":
                $mes = "Fev";
                break;
            case "03":
                $mes = "Mar";
                break;
            case "04":
                $mes = "Abr";
                break;
            case "05":
                $mes = "Mai";
                break;
            case "06":
                $mes = "Jun";
                break;
            case "07":
                $mes = "Jul";
                break;
            case "08":
                $mes = "Ago";
                break;
            case "09":
                $mes = "Set";
                break;
            case "10":
                $mes = "Out";
                break;
            case "11":
                $mes = "Nov";
                break;
            case "12":
                $mes = "Dez";
                break;
        }

        return $mes;
    }

    public static function arrendarFloat($valor)
    {
        $float_arredondado = round($valor * 100) / 100;
        return $float_arredondado;
    }

    public static function translateStatusPayment($status)
    {
        switch ($status) {
            case 'processing':
                return 'Transação está em processo de autorização.';
                break;
            case 'authorized':
                return 'Transação foi autorizada.';
                break;
            case 'paid':
                return 'Transação paga.';
                break;
            case 'refunded':
                return 'Transação estornada completamente.';
                break;
            case 'waiting_payment':
                return 'Transação aguardando pagamento ';
                break;
            case 'pending_refund':
                return 'Aguardando confirmação do estorno solicitado.';
                break;
            case 'refused':
                return 'Transação recusada, não autorizada.';
                break;
            case 'chargedback':
                return 'Transação sofreu chargeback.';
                break;
            case 'analyzing':
                return 'Transação encaminhada para a análise manual feita por um especialista em prevenção a fraude.';
                break;
            case 'pending_review':
                return 'Transação pendente de revisão manual por parte do lojista. Uma transação ficará com esse status por até 48 horas corridas.';
                break;
        }
    }

    public static function getHoursStore($store)
    {
        //Defino o array com as horas disponíveis
        for ($i = (int) explode(':', $store->open_time)[0]; $i < (int) explode(':', $store->close_time)[0]; $i++) {
            $horasDisponiveis[] = ($i < 10 ? "0" . $i : $i) . ":00:00";
            $horasDisponiveis[] = ($i < 10 ? "0" . $i : $i) . ":30:00";
        }
        //Último horário disponível é o timestamps Final
        $horasDisponiveis[] = $store->close_time;
        return $horasDisponiveis;
    }

    public static function user_color($name)
    {
        $hash = self::WordSum($name);
        $h = $hash;
        $result = self::hslToHex(array($h / 100, 30 / 100, 70 / 100));
        return $result;
    }

    public static function WordSum($word)
    {
        $cnt = 0;
        $word = strtoupper(trim($word));
        $len = strlen($word);

        for ($i = 0; $i < $len; $i++) {
            $cnt += ord($word[$i]) - 64;
        }

        return $cnt;
    }

    public static function hslToHex($hsl)
    {
        list($h, $s, $l) = $hsl;

        if ($s == 0) {
            $r = $g = $b = 1;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = self::hue2rgb($p, $q, $h + 1 / 3);
            $g = self::hue2rgb($p, $q, $h);
            $b = self::hue2rgb($p, $q, $h - 1 / 3);
        }

        return self::rgb2hex($r) . self::rgb2hex($g) . self::rgb2hex($b);
    }

    public static function hue2rgb($p, $q, $t)
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    public static function rgb2hex($rgb)
    {
        return str_pad(dechex($rgb * 255), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Função que retorna o status da reserva
     *
     * [0] => Cancelada
     * [1] => Reservada
     * [2] => Confirmada
     * [3] => Em Andamento
     * [4] => Concluida
     */
    public static function getStatusReservation($reservation)
    {
        /**
         * @var Reservation $reservation
         */
        if (!$reservation->status) {
            return 0;
        }

        $date_start_time = Carbon::parse($reservation->booking_date->format('Y-m-d') . ' ' . $reservation->start_time)->format('Y-m-d H:i:s');
        $date_end_time = Carbon::parse($reservation->booking_date->format('Y-m-d') . ' ' . $reservation->end_time)->format('Y-m-d H:i:s');

        if ($reservation->type == 0) {
            if (self::getmissingHoursReservation($reservation) > 48) {
                return 1;
            } elseif (self::getmissingHoursReservation($reservation) < 48 && Carbon::now()->diffInSeconds($date_start_time, false) > 0) {
                return 2;
            }
        } else {
            if (self::getmissingHoursReservation($reservation) > 24) {
                return 1;
            } elseif (self::getmissingHoursReservation($reservation) < 24 && Carbon::now()->diffInSeconds($date_start_time, false) > 0) {
                return 2;
            }
        }

        if (Carbon::now()->diffInSeconds($date_start_time, false) <= 0 && Carbon::now()->diffInSeconds($date_end_time, false) <= 0) {
            return 4;
        }

        if (Carbon::now()->diffInSeconds($date_start_time, false) <= 0 && Carbon::now()->diffInSeconds($date_end_time, false) > 0) {
            // return 3;
            return 2;
        }

        return 1;
    }

    public static function getValueTotalReservation($reservation)
    {
        return ($reservation->type == 0 ? $reservation->total_price : 0) + $reservation->products->sum('price_per_hour');
    }

    public static function getNextReservation($user)
    {
        $next_reservation =  Reservation::where([
            'user_id' => $user->id,
            'status' => 1,
            ['booking_date' , '>=' , Carbon::now()->format('Y-m-d')]
        ])
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->reject(function ($reservation) {
                return $reservation->booking_date == Carbon::now()->format('Y-m-d') && $reservation->start_time < Carbon::now()->format('H:i:s');
            })->first();


        if ($next_reservation) {
            return $next_reservation;
        } else {
            return false;
        }
    }

    public static function checkAvailableRoom($store_id, $date, $start_time, $end_time, $user_id)
    {
        $store = Store::with('rooms')->find($store_id);
        $date = Carbon::parse($date)->format('Y-m-d');
        $start_time = Carbon::parse($start_time)->format('H:i:s');
        $end_time = Carbon::parse($end_time)->format('H:i:s');

        $room_unavailable_dates = Reservation::where([
            'store_id' => $store->id,
            'booking_date' => $date,
            'status' => 1
        ])
            ->orderBy('room_id', 'asc')
            ->get()
            ->filter(function ($reservation) use ($user_id, $start_time) {
                return $reservation->start_time == Carbon::parse($start_time)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->subHour()->format('H:i:s') && $reservation->user_id != $user_id) ||
                    $reservation->start_time == Carbon::parse($start_time)->subMinutes(30)->format('H:i:s') ||
                    $reservation->start_time == Carbon::parse($start_time)->addMinutes(30)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->addHour()->format('H:i:s') && $reservation->user_id != $user_id);
            })->pluck('room_id')->unique();

        return Room::where(['store_id' => $store->id])->whereNotIn('id', $room_unavailable_dates)->orderBy('id', 'asc')->first();
    }

    public static function checkRoomAvailableToBook($store, $date, $start_time, $start_time_static, $end_time_static, $user_id)
    {
        $start_time_priority =  $start_time_static;

        $end_time_priority = $end_time_static;

        $room_unavailable_dates = Reservation::where([
            'store_id' => $store->id,
            'booking_date' => $date,
            'status' => 1
        ])
            ->orderBy('room_id', 'asc')
            ->get()
            ->filter(function ($reservation) use ($user_id, $start_time) {
                return $reservation->start_time == Carbon::parse($start_time)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->subHour()->format('H:i:s') && $reservation->user_id != $user_id) ||
                    $reservation->start_time == Carbon::parse($start_time)->subMinutes(30)->format('H:i:s') ||
                    $reservation->start_time == Carbon::parse($start_time)->addMinutes(30)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->addHour()->format('H:i:s') && $reservation->user_id != $user_id);
            })->pluck('room_id')->unique();

        $priority_room1 = Room::whereNotIn('id', $room_unavailable_dates)->where(['store_id' => $store->id])->orderBy('id', 'asc')->get()->filter(function ($room) use ($store, $date, $room_unavailable_dates, $start_time_priority, $end_time_priority, $user_id) {
            return !Reservation::whereNotIn('room_id', $room_unavailable_dates)->where([
                'room_id' => $room->id,
                'store_id' => $store->id,
                'booking_date' => $date,
                'status' => 1
            ])->where(function ($query) use ($start_time_priority, $end_time_priority, $user_id) {
                $query->whereBetween('start_time', [Carbon::parse($start_time_priority)->format('H:i:s'), Carbon::parse($end_time_priority)->subMinutes(30)->format('H:i:s')])
                    ->orWhere(function ($query) use ($start_time_priority, $user_id) {
                        $query->where('end_time', $start_time_priority)->where('user_id', '!=', $user_id);
                    })
                    ->orWhere(function ($query) use ($end_time_priority, $user_id) {
                        $query->where('start_time', $end_time_priority)->where('user_id', '!=', $user_id);
                    });
            })
                ->where(function ($query) {
                    $query->whereNotBetween('created_at', [Carbon::now()->subSeconds(10)->format('Y-m-d H:i:s'), Carbon::now()->addSeconds(10)->format('Y-m-d H:i:s')]);
                })
                ->count();
        })->filter(function ($room) use ($store, $date, $room_unavailable_dates, $start_time_priority, $end_time_priority, $user_id) {
            return Reservation::whereNotIn('room_id', $room_unavailable_dates)->where([
                'room_id' => $room->id,
                'store_id' => $store->id,
                'booking_date' => $date,
                'status' => 1
            ])->where(function ($query) use ($start_time_priority) {
                $query->where('start_time', Carbon::parse($start_time_priority)->subHour()->format('H:i:s'))->orWhere('start_time', Carbon::parse($start_time_priority)->addHour()->format('H:i:s'));
            })
                ->count();
        })->reduce(function ($priority_room, $item) {
            return $item;
        });

        $priority_room2 = Room::whereNotIn('id', $room_unavailable_dates)->where(['store_id' => $store->id])->orderBy('id', 'desc')->get()->filter(function ($room) use ($store, $date, $room_unavailable_dates, $start_time_priority, $end_time_priority, $user_id) {
            return !Reservation::whereNotIn('room_id', $room_unavailable_dates)->where([
                'room_id' => $room->id,
                'store_id' => $store->id,
                'booking_date' => $date,
                'status' => 1
            ])->where(function ($query) use ($start_time_priority, $end_time_priority, $user_id) {
                $query->whereBetween('start_time', [Carbon::parse($start_time_priority)->format('H:i:s'), Carbon::parse($end_time_priority)->subMinutes(30)->format('H:i:s')])
                    ->orWhere(function ($query) use ($start_time_priority, $user_id) {
                        $query->where('end_time', $start_time_priority)->where('user_id', '!=', $user_id);
                    })
                    ->orWhere(function ($query) use ($end_time_priority, $user_id) {
                        $query->where('start_time', $end_time_priority)->where('user_id', '!=', $user_id);
                    });
            })->where(function ($query) {
                $query->whereNotBetween('created_at', [Carbon::now()->subSeconds(10)->format('Y-m-d H:i:s'), Carbon::now()->addSeconds(10)->format('Y-m-d H:i:s')]);
            })
                ->count();
        })->reduce(function ($priority_room, $item) {
            return $item;
        });


        $room = Room::where(['store_id' => $store->id])->whereNotIn('id', $room_unavailable_dates)->orderBy('id', 'asc')->first();
        if ($priority_room1) {
            return $priority_room1;
        } elseif ($priority_room2) {
            return $priority_room2;
        } else {
            return $room;
        }
    }

    public static function quantityAvailableRoom($store_id, $date, $start_time, $end_time, $user_id, $reservation = false)
    {
        $store = Store::with('rooms')->find($store_id);
        $date = Carbon::parse($date)->format('Y-m-d');
        $start_time = Carbon::parse($start_time)->format('H:i:s');

        $room_unavailable_dates = Reservation::where([
            'store_id' => $store->id,
            'booking_date' => $date,
            'status' => 1
        ])
            ->orderBy('room_id', 'asc')
            ->get()
            ->filter(function ($reservation) use ($user_id, $start_time) {
                return $reservation->start_time == Carbon::parse($start_time)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->subHour()->format('H:i:s') && $reservation->user_id != $user_id) ||
                    $reservation->start_time == Carbon::parse($start_time)->subMinutes(30)->format('H:i:s') ||
                    $reservation->start_time == Carbon::parse($start_time)->addMinutes(30)->format('H:i:s') ||
                    ($reservation->start_time == Carbon::parse($start_time)->addHour()->format('H:i:s') && $reservation->user_id != $user_id);
            })->pluck('room_id')->unique();

        return Room::where(['store_id' => $store->id])->whereNotIn('id', $room_unavailable_dates)->orderBy('id', 'asc')->count();
    }

    public static function getReservationContract(Reservation $reservation)
    {
        if (!$reservation->type) {
            return null;
        }

        $contracts_id = ContractUser::where([
            'user_id' => $reservation->user_id
        ])->pluck('contract_id');

        if (!$contracts_id->count()) {
            return null;
        }

        $date = $reservation->booking_date;

        $contract = Contract::whereIn('id', $contracts_id)
            ->where('is_active', 1)
            ->where('status', 1)
            ->get()
            ->filter(function ($contract) use ($date) {
                return Carbon::parse($date)->between(Carbon::parse($contract->begin_date), Carbon::parse($contract->end_date)->addDay());
            })->reduce(function ($contract, $item) {
                return $item;
            });
        if (!$contract) {
            return false;
        }

        return $contract;
    }

    public static function formatMoneyDb($str)
    {
        $str = preg_replace('/\D/', '', $str);

        $str = number_format(($str / 100), 2);
        $str = str_replace(',', '', $str);
        return $str;
    }

    public static function whenCanAddContractAddend($contract_id, $user = null)
    {
        /**
         * @var Contract $contract
         */

        if ($user != null) {
            $contract = self::getContractActive($user);
        } else {
            $contract = Contract::find($contract_id);
        }

        if (!$contract) {
            return false;
        }
        /*
         * 01/02/2021 à 30/04/2021
         *
         * */

        if (($contract->primary_user[0]->id == Auth::user()->id) || Auth::user()->role == 0) {
            return $contract->price_per_hour > 0 && Carbon::now()->between($contract->begin_date, $contract->end_date);
        }
    }

    public static function clientIsDefaultingTime($user)
    {
        if ($user->role != 2) {
            return 1;
        }

        if (!$user->person->non_payment) {
            return 1;
        }

        $date = Carbon::now()->setDay(12)->setDate($user->person->non_payment_date->format('Y'), $user->person->non_payment_date->format('m'), 12)->setTime(0, 0, 0)->format('Y-m-d H:i:s');

        return Carbon::now()->diffInSeconds($date, false);
    }

    public static function getNotification($type = null)
    {
        if (Auth::user()->role != 2) {
            return false;
        }

        $notification_users = NotificationUser::where(['user_id' => Auth::user()->id])->pluck('notification_id')->toArray();

        $notifications = Notification::orderBy('created_at', 'desc')
            ->whereNotIn('id', $notification_users)
            ->when($type != null, function ($query) use ($type) {
                $query->where('type', $type);
            })->get()
            ->filter(function ($notification) {
                return Auth::user()->can("view", $notification);
            });

        return $notifications->last();
    }
}
