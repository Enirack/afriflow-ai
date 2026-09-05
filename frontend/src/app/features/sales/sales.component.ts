import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { forkJoin } from 'rxjs';
import { CustomerService } from '../../core/services/customer.service';
import { ProductService } from '../../core/services/product.service';
import { SaleService } from '../../core/services/sale.service';
import { Customer } from '../../core/models/customer.model';
import { Product } from '../../core/models/product.model';
import {
  PAYMENT_METHOD_LABELS,
  PaymentMethod,
  Sale,
  SALE_STATUS_LABELS,
} from '../../core/models/sale.model';
import { FcfaPipe } from '../../shared/pipes/fcfa.pipe';

@Component({
  selector: 'app-sales',
  imports: [ReactiveFormsModule, FcfaPipe, DatePipe],
  template: `
    <div class="page-header">
      <div>
        <h1>Ventes</h1>
        <p class="page-subtitle">Enregistrez vos ventes et suivez les paiements.</p>
      </div>
      <button class="btn btn-primary" type="button" (click)="toggleForm()">
        {{ showForm() ? 'Annuler' : '+ Nouvelle vente' }}
      </button>
    </div>

    @if (showForm()) {
      <form class="card form-card" [formGroup]="form" (ngSubmit)="submit()">
        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-grid">
          <div class="form-field">
            <label for="customerId">Client</label>
            <select id="customerId" formControlName="customerId">
              <option [ngValue]="null">Client de passage</option>
              @for (customer of customers(); track customer.id) {
                <option [ngValue]="customer.id">{{ customer.name }}</option>
              }
            </select>
          </div>
          <div class="form-field">
            <label for="paymentMethod">Mode de paiement</label>
            <select id="paymentMethod" formControlName="paymentMethod">
              @for (method of paymentMethods; track method) {
                <option [value]="method">{{ paymentMethodLabels[method] }}</option>
              }
            </select>
          </div>
          <div class="form-field">
            <label for="discount">Remise (FCFA)</label>
            <input id="discount" type="number" min="0" formControlName="discount" />
          </div>
        </div>

        <h3>Articles</h3>
        <div class="items-table">
          @for (row of itemRows(); track row.key; let i = $index) {
            <div class="item-row">
              <select
                [formControl]="row.productId"
                (change)="onProductChange(i)"
              >
                <option [ngValue]="null" disabled>Choisir un produit</option>
                @for (product of products(); track product.id) {
                  <option [ngValue]="product.id">{{ product.name }}</option>
                }
              </select>
              <input type="number" min="1" [formControl]="row.quantity" placeholder="Qté" />
              <span class="line-total">{{ lineTotal(i) | fcfa }}</span>
              <button
                type="button"
                class="btn btn-danger"
                (click)="removeItem(i)"
                [disabled]="itemRows().length === 1"
              >
                Retirer
              </button>
            </div>
          }
        </div>
        <button type="button" class="btn btn-secondary add-item" (click)="addItem()">
          + Ajouter un article
        </button>

        <div class="form-total">Total : {{ formTotal() | fcfa }}</div>

        <button class="btn btn-primary" type="submit" [disabled]="form.invalid || saving()">
          {{ saving() ? 'Enregistrement...' : 'Enregistrer la vente' }}
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
      } @else if (sales().length === 0) {
        <div class="empty-state">Aucune vente pour le moment.</div>
      } @else {
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Vendeur</th>
              <th>Total</th>
              <th>Payé</th>
              <th>Solde</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @for (sale of sales(); track sale.id) {
              <tr>
                <td>{{ sale.saleDate | date: 'dd/MM/yyyy HH:mm' }}</td>
                <td>{{ sale.seller.fullName }}</td>
                <td>{{ sale.totalAmount | fcfa }}</td>
                <td>{{ sale.amountPaid | fcfa }}</td>
                <td>{{ sale.balanceDue | fcfa }}</td>
                <td>
                  <span class="badge" [class]="statusBadgeClass(sale.status)">
                    {{ statusLabels[sale.status] }}
                  </span>
                </td>
                <td>
                  @if (sale.status !== 'paid') {
                    @if (payingForSaleId() === sale.id) {
                      <div class="payment-form">
                        <input
                          type="number"
                          min="1"
                          [formControl]="paymentAmount"
                          placeholder="Montant"
                          [disabled]="paymentSaving()"
                        />
                        <button
                          type="button"
                          class="btn btn-primary btn-sm"
                          (click)="confirmPayment(sale)"
                          [disabled]="paymentSaving()"
                        >
                          {{ paymentSaving() ? '...' : 'Valider' }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-secondary btn-sm"
                          (click)="cancelPayment()"
                          [disabled]="paymentSaving()"
                        >
                          Annuler
                        </button>
                      </div>
                      @if (paymentError()) {
                        <div class="payment-error">{{ paymentError() }}</div>
                      }
                    } @else {
                      <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        (click)="startPayment(sale)"
                      >
                        + Paiement
                      </button>
                    }
                  }
                </td>
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

      h3 {
        margin: 8px 0 10px;
      }

      .items-table {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 10px;
      }

      .item-row {
        display: grid;
        grid-template-columns: 2fr 100px 140px auto;
        gap: 10px;
        align-items: center;
      }

      .item-row select,
      .item-row input {
        padding: 8px 10px;
        border: 1px solid var(--color-border);
        border-radius: 8px;
      }

      .line-total {
        font-weight: 600;
        text-align: right;
      }

      .add-item {
        margin-bottom: 16px;
      }

      .form-total {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 16px;
      }

      .btn-sm {
        padding: 5px 10px;
        font-size: 12.5px;
      }

      .payment-form {
        display: flex;
        gap: 6px;
        align-items: center;
      }

      .payment-form input {
        width: 90px;
        padding: 5px 8px;
        border: 1px solid var(--color-border);
        border-radius: 6px;
      }

      .payment-error {
        color: var(--color-danger);
        font-size: 12px;
        margin-top: 4px;
      }

      .btn-retry {
        margin-left: 12px;
      }
    `,
  ],
})
export class SalesComponent {
  private readonly fb = inject(FormBuilder);
  private readonly saleService = inject(SaleService);
  private readonly productService = inject(ProductService);
  private readonly customerService = inject(CustomerService);

  protected readonly paymentMethodLabels = PAYMENT_METHOD_LABELS;
  protected readonly paymentMethods = Object.keys(PAYMENT_METHOD_LABELS) as PaymentMethod[];
  protected readonly statusLabels = SALE_STATUS_LABELS;

  protected readonly sales = signal<Sale[]>([]);
  protected readonly products = signal<Product[]>([]);
  protected readonly customers = signal<Customer[]>([]);
  protected readonly showForm = signal(false);
  protected readonly saving = signal(false);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly loadError = signal<string | null>(null);

  protected readonly payingForSaleId = signal<number | null>(null);
  protected readonly paymentAmount = new FormControl<number | null>(null);
  protected readonly paymentSaving = signal(false);
  protected readonly paymentError = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    customerId: this.fb.control<number | null>(null),
    paymentMethod: this.fb.nonNullable.control<PaymentMethod>('cash'),
    discount: [0, [Validators.required, Validators.min(0)]],
  });

  private itemRowKey = 0;
  protected readonly itemRows = signal(
    [this.createItemRow()],
  );

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.loadError.set(null);
    forkJoin({
      sales: this.saleService.list(),
      products: this.productService.list(),
      customers: this.customerService.list(),
    }).subscribe({
      next: ({ sales, products, customers }) => {
        const sorted = [...sales.member].sort(
          (a, b) => new Date(b.saleDate).getTime() - new Date(a.saleDate).getTime(),
        );
        this.sales.set(sorted);
        this.products.set(products.member);
        this.customers.set(customers.member);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.loadError.set('Impossible de charger les ventes.');
      },
    });
  }

  toggleForm(): void {
    if (this.showForm()) {
      this.form.reset({ customerId: null, paymentMethod: 'cash', discount: 0 });
      this.itemRowKey = 0;
      this.itemRows.set([this.createItemRow()]);
      this.error.set(null);
    }
    this.showForm.set(!this.showForm());
  }

  private createItemRow() {
    return {
      key: this.itemRowKey++,
      productId: this.fb.control<number | null>(null),
      quantity: this.fb.nonNullable.control<number>(1),
    };
  }

  addItem(): void {
    this.itemRows.update((rows) => [...rows, this.createItemRow()]);
  }

  removeItem(index: number): void {
    this.itemRows.update((rows) => rows.filter((_, i) => i !== index));
  }

  onProductChange(_index: number): void {
    // triggers recomputation of the total via the template's signal reads
  }

  private productPrice(productId: number | null): number {
    if (productId === null) {
      return 0;
    }
    return Number(this.products().find((p) => p.id === productId)?.unitPrice ?? 0);
  }

  lineTotal(index: number): number {
    const row = this.itemRows()[index];
    return this.productPrice(row.productId.value) * (row.quantity.value ?? 0);
  }

  formTotal(): number {
    const itemsTotal = this.itemRows().reduce(
      (sum, _, i) => sum + this.lineTotal(i),
      0,
    );
    return Math.max(0, itemsTotal - (this.form.controls.discount.value ?? 0));
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    const rows = this.itemRows().filter((row) => row.productId.value !== null);
    if (rows.length === 0) {
      this.error.set('Ajoutez au moins un article.');
      return;
    }

    const productIds = rows.map((row) => row.productId.value);
    if (new Set(productIds).size !== productIds.length) {
      this.error.set(
        'Un même produit apparaît sur plusieurs lignes : additionnez les quantités sur une seule ligne.',
      );
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    const raw = this.form.getRawValue();

    this.saleService
      .create({
        customerId: raw.customerId,
        paymentMethod: raw.paymentMethod,
        discount: String(raw.discount),
        items: rows.map((row) => ({
          productId: row.productId.value as number,
          quantity: row.quantity.value,
        })),
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.showForm.set(false);
          this.form.reset({ customerId: null, paymentMethod: 'cash', discount: 0 });
          this.itemRowKey = 0;
          this.itemRows.set([this.createItemRow()]);
          this.reload();
        },
        error: () => {
          this.saving.set(false);
          this.error.set('Impossible d\'enregistrer cette vente.');
        },
      });
  }

  statusBadgeClass(status: Sale['status']): string {
    if (status === 'paid') return 'badge-success';
    if (status === 'partially_paid') return 'badge-warning';
    return 'badge-danger';
  }

  startPayment(sale: Sale): void {
    this.paymentAmount.setValue(Number(sale.balanceDue));
    this.paymentError.set(null);
    this.payingForSaleId.set(sale.id);
  }

  cancelPayment(): void {
    this.payingForSaleId.set(null);
    this.paymentError.set(null);
  }

  confirmPayment(sale: Sale): void {
    const amount = this.paymentAmount.value;
    if (!amount || amount <= 0) {
      return;
    }

    this.paymentSaving.set(true);
    this.paymentError.set(null);

    this.saleService
      .recordPayment({
        saleId: sale.id,
        amount: String(amount),
        method: sale.paymentMethod,
      })
      .subscribe({
        next: () => {
          this.paymentSaving.set(false);
          this.payingForSaleId.set(null);
          this.reload();
        },
        error: () => {
          this.paymentSaving.set(false);
          this.paymentError.set('Impossible d\'enregistrer ce paiement.');
        },
      });
  }
}
