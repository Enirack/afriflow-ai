import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ProductService } from '../../core/services/product.service';
import { Product } from '../../core/models/product.model';
import { FcfaPipe } from '../../shared/pipes/fcfa.pipe';

@Component({
  selector: 'app-products',
  imports: [ReactiveFormsModule, FcfaPipe],
  template: `
    <div class="page-header">
      <div>
        <h1>Produits</h1>
        <p class="page-subtitle">Gérez votre catalogue et vos niveaux de stock.</p>
      </div>
      <button class="btn btn-primary" type="button" (click)="toggleForm()">
        {{ showForm() ? 'Annuler' : '+ Nouveau produit' }}
      </button>
    </div>

    @if (showForm()) {
      <form class="card form-card" [formGroup]="form" (ngSubmit)="submit()">
        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-grid">
          <div class="form-field">
            <label for="name">Nom du produit</label>
            <input id="name" type="text" formControlName="name" />
          </div>
          <div class="form-field">
            <label for="sku">Référence (SKU)</label>
            <input id="sku" type="text" formControlName="sku" />
          </div>
          <div class="form-field">
            <label for="unitPrice">Prix unitaire (FCFA)</label>
            <input id="unitPrice" type="number" min="0" formControlName="unitPrice" />
          </div>
          <div class="form-field">
            <label for="stockQuantity">Quantité en stock</label>
            <input id="stockQuantity" type="number" min="0" formControlName="stockQuantity" />
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
      } @else if (products().length === 0) {
        <div class="empty-state">Aucun produit pour le moment.</div>
      } @else {
        <table class="data-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>SKU</th>
              <th>Prix unitaire</th>
              <th>Stock</th>
            </tr>
          </thead>
          <tbody>
            @for (product of products(); track product.id) {
              <tr>
                <td>{{ product.name }}</td>
                <td>{{ product.sku || '—' }}</td>
                <td>{{ product.unitPrice | fcfa }}</td>
                <td>{{ product.stockQuantity }}</td>
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
export class ProductsComponent {
  private readonly fb = inject(FormBuilder);
  private readonly productService = inject(ProductService);

  protected readonly products = signal<Product[]>([]);
  protected readonly showForm = signal(false);
  protected readonly saving = signal(false);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly loadError = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    sku: [''],
    unitPrice: [0, [Validators.required, Validators.min(0)]],
    stockQuantity: [0, [Validators.required, Validators.min(0)]],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.loadError.set(null);
    this.productService.list().subscribe({
      next: (collection) => {
        this.products.set(collection.member);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.loadError.set('Impossible de charger les produits.');
      },
    });
  }

  toggleForm(): void {
    if (this.showForm()) {
      this.form.reset({ name: '', sku: '', unitPrice: 0, stockQuantity: 0 });
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
    this.productService
      .create({
        name: raw.name,
        sku: raw.sku || null,
        unitPrice: String(raw.unitPrice),
        stockQuantity: raw.stockQuantity,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.showForm.set(false);
          this.form.reset({ name: '', sku: '', unitPrice: 0, stockQuantity: 0 });
          this.reload();
        },
        error: () => {
          this.saving.set(false);
          this.error.set("Impossible d'enregistrer ce produit.");
        },
      });
  }
}
