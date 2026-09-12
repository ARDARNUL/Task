<?php

namespace App\Enums;

enum ParseStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Ok = 'ok';
    case Failed = 'failed';
    case LayoutChanged = 'layout_changed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'В очереди',
            self::Running => 'В процессе',
            self::Ok => 'Успешно',
            self::Failed => 'Ошибка',
            self::LayoutChanged => 'Изменилась разметка источника',
        };
    }
}