<?php 

namespace Golem15\Apparatus\Classes;

use Carbon\Carbon;

class HumanDateExtension
{
    public function register()
    {
        $twig = app('twig');
        $twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        $twig->addFilter(new \Twig\TwigFilter('humanDate', [$this, 'humanDate']));
    }

    public function humanDateFilter($dateString)
    {
        $date = Carbon::parse($dateString);
        $now = Carbon::now();
        
        // Today
        if ($date->isToday()) {
            return trans('golem15.apparatus::lang.human.date.today', ['time' => $date->format('H:i')]);
        }

        // Tomorrow
        if ($date->isTomorrow()) {
            return trans('golem15.apparatus::lang.human.date.tomorrow', ['time' => $date->format('H:i')]);
        }

        // Day of the week within the same week
        if ($date->isSameWeek($now) && $date->gt($now)) {
            return trans('golem15.apparatus::lang.human.date.this_week', [
                'day' => $date->format('l'),
                'time' => $date->format('H:i'),
            ]);
        }

        // Next week
        if ($date->isNextWeek()) {
            return trans('golem15.apparatus::lang.human.date.next_week', ['day' => $date->format('l')]);
        }

        // In X weeks
        $weeksDiff = $date->diffInWeeks($now);
        if ($weeksDiff > 1 && $weeksDiff <= 4) {
            return trans('golem15.apparatus::lang.human.date.in_weeks', ['count' => $weeksDiff]);
        }

        // In X months
        $monthsDiff = $date->diffInMonths($now);
        if ($monthsDiff > 1 && $monthsDiff <= 12) {
            return trans('golem15.apparatus::lang.human.date.in_months', ['count' => $monthsDiff]);
        }

        // Next year
        if ($date->isNextYear()) {
            return trans('golem15.apparatus::lang.human.date.next_year');
        }

        // In X years
        $yearsDiff = $date->diffInYears($now);
        if ($yearsDiff >= 1) {
            return trans('golem15.apparatus::lang.human.date.in_years', ['count' => $yearsDiff]);
        }

        // Default: exact date if none of the above conditions are met
        return trans('golem15.apparatus::lang.human.date.exact_date', [
            'day' => $date->format('l'),
            'date' => $date->format('d M Y'),
            'time' => $date->format('H:i'),
        ]);
    }
}