import { SlicePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ExpenseService } from '../../core/services/expense.service';
import {
  EXPENSE_CATEGORY_LABELS,
  Expense,
  ExpenseCategory,
} from '../../core/models/expense.model';
import { FcfaPipe } from '../../shared/pipes/fcfa.pipe';

@Component({
  selector: 'app-expenses',
  imports: [ReactiveFormsModule, FcfaPipe, SlicePipe],
  template: `
    <div class="page-header">
      <div>
        <h1>Dépenses</h1>
        <p class="page-subtitle">Suivez les sorties d'argent de votre entreprise.</p>
      </div>
      <button class="btn btn-primary" type="button" (click)="toggleForm()">
        {{ showForm() ? 'Annuler' : '+ Nouvelle dépense' }}
      </button>
    </div>

    @if (showForm()) {
      <form class="card form-card" [formGroup]="form" (ngSubmit)="submit()">
        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-grid">
          <div class="form-field">
            <label for="category">Catégorie</label>
            <select id="category" formControlName="category">
              @for (category of categories; track category) {
                <option [value]="category">{{ categoryLabels[category] }}</option>
              }
            </select>
          </div>
          <div class="form-field">
            <label for="amount">Montant (FCFA)</label>
            <input id="amount" type="number" min="0" formControlName="amount" />
          </div>
          <div class="form-field">
            <label for="expenseDate">Date</label>
            <input id="expenseDate" type="date" formControlName="expenseDate" />
          </div>
          <div class="form-field">
            <label for="description">Description</label>
            <input id="description" type="text" formControlName="description" />
          </div>
        </div>

        <button class="btn btn-primary" type="submit" [disabled]="form.invalid || saving()">
          {{ saving() ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </form>
    }

    <div class="card">
      @if (loadError()) {
        <div class="alert-error">
          {{ loadError() }}
          <button type="button" class="btn btn-secondary btn-retry" (click)="reload()">Réessayer</button>
        </div>
      } @else if (loading()) {
        <div class="empty-state">Chargement...</div>
      } @else if (expenses().length === 0) {
        <div class="empty-state">Aucune dépense pour le moment.</div>
      } @else {
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Catégorie</th>
              <th>Description</th>
              <th>Montant</th>
            </tr>
          </thead>
          <tbody>
            @for (expense of expenses(); track expense.id) {
              <tr>
                <td>{{ expense.expenseDate | slice: 0 : 10 }}</td>
                <td>{{ categoryLabels[expense.category] }}</td>
                <td>{{ expense.description || '—' }}</td>
                <td>{{ expense.amount | fcfa }}</td>
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

      .btn-retry {
        margin-left: 12px;
      }
    `,
  ],
})
export class ExpensesComponent {
  private readonly fb = inject(FormBuilder);
  private readonly expenseService = inject(ExpenseService);

  protected readonly categoryLabels = EXPENSE_CATEGORY_LABELS;
  protected readonly categories = Object.keys(EXPENSE_CATEGORY_LABELS) as ExpenseCategory[];

  protected readonly expenses = signal<Expense[]>([]);
  protected readonly showForm = signal(false);
  protected readonly saving = signal(false);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly loadError = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    category: ['transport' as ExpenseCategory, Validators.required],
    amount: [0, [Validators.required, Validators.min(1)]],
    expenseDate: [new Date().toISOString().slice(0, 10), Validators.required],
    description: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.loadError.set(null);
    this.expenseService.list().subscribe({
      next: (collection) => {
        this.expenses.set(collection.member);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.loadError.set('Impossible de charger les dépenses.');
      },
    });
  }

  toggleForm(): void {
    if (this.showForm()) {
      this.form.reset({
        category: 'transport',
        amount: 0,
        expenseDate: new Date().toISOString().slice(0, 10),
        description: '',
      });
      this.error.set(null);
    }
    this.showForm.set(!this.showForm());
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    const raw = this.form.getRawValue();
    this.expenseService
      .create({
        category: raw.category,
        amount: String(raw.amount),
        description: raw.description || null,
        expenseDate: `${raw.expenseDate}T00:00:00+00:00`,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.showForm.set(false);
          this.form.reset({
            category: 'transport',
            amount: 0,
            expenseDate: new Date().toISOString().slice(0, 10),
            description: '',
          });
          this.reload();
        },
        error: () => {
          this.saving.set(false);
          this.error.set("Impossible d'enregistrer cette dépense.");
        },
      });
  }
}
