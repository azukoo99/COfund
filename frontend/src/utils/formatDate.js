import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/id'

dayjs.extend(relativeTime)
dayjs.locale('id')

export function formatDate(date, format = 'DD MMMM YYYY') {
  if (!date) return '-'
  return dayjs(date).format(format)
}

export function formatRelative(date) {
  if (!date) return '-'
  return dayjs(date).fromNow()
}
