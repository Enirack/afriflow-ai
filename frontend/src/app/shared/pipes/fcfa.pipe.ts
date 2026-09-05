import { Pipe, PipeTransform } from '@angular/core';

@Pipe({ name: 'fcfa' })
export class FcfaPipe implements PipeTransform {
  transform(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
      return '—';
    }

    const amount = typeof value === 'string' ? Number(value) : value;

    if (Number.isNaN(amount)) {
      return '—';
    }

    const formatted = new Intl.NumberFormat('fr-FR', {
      maximumFractionDigits: 0,
    }).format(amount);

    return `${formatted} FCFA`;
  }
}
