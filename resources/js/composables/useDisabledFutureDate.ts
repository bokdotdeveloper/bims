import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';

/** For Ant Design Vue `DatePicker` / `RangePicker` `disabled-date`: disallow days after today (local calendar). */
export function disabledFutureDate(current: Dayjs): boolean {
    return Boolean(current && current.isAfter(dayjs(), 'day'));
}
