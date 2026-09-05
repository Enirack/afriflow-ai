import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { CustomerService } from '../../core/services/customer.service';
import { Customer } from '../../core/models/customer.model';

@Component({
  selector: 'app-customers',
  imports: [ReactiveFormsModule],
  template: `
    <div class="page-header">
      <div>
        <h1>Clients</h1>
        <p class="page-subtitle">Retrouvez vos clients et leurs coordonnées.</p>
      </div>
      <button class="btn btn-primary" type="button" (click)="showForm.set(!showForm())">
        {{ showForm() ? 'Annuler' : '+ Nouveau client' }}
      </button>
    </div>

    @if (showForm()) {
      <form class="card form-card" [formGroup]="form" (ngSubmit)="submit()">
        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-grid">
          <div class="form-field">
            <label for="name">Nom</label>
            <input id="name" type="text" formControlName="name" />
          </div>
          <div class="form-field">
            <label for="phone">Téléphone</label>
            <input id="phone" type="text" formControlName="phone" />
          </div>
          <div class="form-field">
            <label for="email">Email</label>
            <input id="email" type="email" formControlName="email" />
          </div>
          <div class="form-field">
            <label for="address">Adresse</label>
            <input id="address" type="text" formControlName="address" />
          </div>
        </div>

        <button class="btn btn-primary" type="submit" [disabled]="form.invalid || saving()">
          {{ saving() ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </form>
    }

    <div class="card">
      @if (customers().length === 0) {
        <div class="empty-state">Aucun client pour le moment.</div>
      } @else {
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Téléphone</th>
              <th>Email</th>
              <th>Adresse</th>
            </tr>
          </thead>
          <tbody>
            @for (customer of customers(); track customer.id) {
              <tr>
                <td>{{ customer.name }}</td>
                <td>{{ customer.phone || '—' }}</td>
                <td>{{ customer.email || '—' }}</td>
                <td>{{ customer.address || '—' }}</td>
              </tr>
            }
          </tbody>
        </table>
      }
    </div>
  `,
  styles: [
    `
      .page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 20px;
      }

      .page-subtitle {
        color: var(--color-text-muted);
        margin: 0;
      }

      .form-card {
        margin-bottom: 20px;
      }

      .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0 16px;
      }
    `,
  ],
})
export class CustomersComponent {
  private readonly fb = inject(FormBuilder);
  private readonly customerService = inject(CustomerService);

  protected readonly customers = signal<Customer[]>([]);
  protected readonly showForm = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    phone: [''],
    email: ['', Validators.email],
    address: [''],
  });

  constructor() {
    this.reload();
  }

  private reload(): void {
    this.customerService.list().subscribe((collection) => this.customers.set(collection.member));
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    const raw = this.form.getRawValue();
    this.customerService
      .create({
        name: raw.name,
        phone: raw.phone || null,
        email: raw.email || null,
        address: raw.address || null,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.showForm.set(false);
          this.form.reset({ name: '', phone: '', email: '', address: '' });
          this.reload();
        },
        error: () => {
          this.saving.set(false);
          this.error.set('Impossible d\'enregistrer ce client.');
        },
      });
  }
}
