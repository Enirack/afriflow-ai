import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { CustomerService } from '../../core/services/customer.service';
import { ProductService } from '../../core/services/product.service';
import { SaleService } from '../../core/services/sale.service';
import { Sale, SALE_STATUS_LABELS } from '../../core/models/sale.model';
import { FcfaPipe } from '../../shared/pipes/fcfa.pipe';

@Component({
  selector: 'app-dashboard',
  imports: [RouterLink, FcfaPipe, DatePipe],
  template: `
    <h1>Tableau de bord</h1>
    <p class="page-subtitle">Vue d'ensemble de votre activité.</p>

    <div class="stats-grid">
      <div class="card stat-card">
        <span class="stat-label">Ventes enregistrées</span>
        <span class="stat-value">{{ salesCount() }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Chiffre d'affaires total</span>
        <span class="stat-value">{{ totalRevenue() | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Créances (impayés)</span>
        <span class="stat-value">{{ totalBalanceDue() | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Produits</span>
        <span class="stat-value">{{ productsCount() }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Clients</span>
        <span class="stat-value">{{ customersCount() }}</span>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Ventes récentes</h2>
        <a routerLink="/sales" class="btn btn-secondary">Voir toutes les ventes</a>
      </div>

      @if (recentSales().length === 0) {
        <div class="empty-state">Aucune vente enregistrée pour le moment.</div>
      } @else {
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>Total</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @for (sale of recentSales(); track sale.id) {
              <tr>
                <td>{{ sale.saleDate | date: 'dd/MM/yyyy HH:mm' }}</td>
                <td>{{ sale.customer ? 'Client #' + sale.customer.split('/').pop() : 'Client de passage' }}</td>
                <td>{{ sale.totalAmount | fcfa }}</td>
                <td>
                  <span class="badge" [class]="statusBadgeClass(sale.status)">
                    {{ statusLabel(sale.status) }}
                  </span>
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
      .page-subtitle {
        color: var(--color-text-muted);
        margin: 0 0 24px;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
      }

      .stat-card {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }

      .stat-label {
        font-size: 12.5px;
        color: var(--color-text-muted);
      }

      .stat-value {
        font-size: 22px;
        font-weight: 700;
      }

      .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
      }

      .card-header h2 {
        margin: 0;
      }
    `,
  ],
})
export class DashboardComponent {
  private readonly saleService = inject(SaleService);
  private readonly productService = inject(ProductService);
  private readonly customerService = inject(CustomerService);

  protected readonly salesCount = signal(0);
  protected readonly totalRevenue = signal(0);
  protected readonly totalBalanceDue = signal(0);
  protected readonly productsCount = signal(0);
  protected readonly customersCount = signal(0);
  protected readonly recentSales = signal<Sale[]>([]);

  constructor() {
    forkJoin({
      sales: this.saleService.list(),
      products: this.productService.list(),
      customers: this.customerService.list(),
    }).subscribe(({ sales, products, customers }) => {
      this.salesCount.set(sales.totalItems);
      this.productsCount.set(products.totalItems);
      this.customersCount.set(customers.totalItems);

      const revenue = sales.member.reduce((sum, sale) => sum + Number(sale.totalAmount), 0);
      const balanceDue = sales.member.reduce((sum, sale) => sum + Number(sale.balanceDue), 0);
      this.totalRevenue.set(revenue);
      this.totalBalanceDue.set(balanceDue);

      const sorted = [...sales.member].sort(
        (a, b) => new Date(b.saleDate).getTime() - new Date(a.saleDate).getTime(),
      );
      this.recentSales.set(sorted.slice(0, 5));
    });
  }

  statusLabel(status: Sale['status']): string {
    return SALE_STATUS_LABELS[status];
  }

  statusBadgeClass(status: Sale['status']): string {
    if (status === 'paid') return 'badge-success';
    if (status === 'partially_paid') return 'badge-warning';
    return 'badge-danger';
  }
}
