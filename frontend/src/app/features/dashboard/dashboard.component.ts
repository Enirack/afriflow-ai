import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { SaleService } from '../../core/services/sale.service';
import { StatsRange, StatsService } from '../../core/services/stats.service';
import { RevenuePoint, StatsSummary, TopCustomer, TopProduct } from '../../core/models/stats.model';
import { Sale, SALE_STATUS_LABELS } from '../../core/models/sale.model';
import { FcfaPipe } from '../../shared/pipes/fcfa.pipe';
import { RevenueChartComponent } from '../../shared/charts/revenue-chart.component';

type Period = 'today' | 'week' | 'month';

@Component({
  selector: 'app-dashboard',
  imports: [RouterLink, FcfaPipe, DatePipe, RevenueChartComponent],
  template: `
    <div class="page-header">
      <div>
        <h1>Tableau de bord</h1>
        <p class="page-subtitle">Vue d'ensemble de votre activité.</p>
      </div>
      <div class="period-switch">
        <button
          type="button"
          class="btn"
          [class.btn-primary]="period() === 'today'"
          [class.btn-secondary]="period() !== 'today'"
          (click)="setPeriod('today')"
        >
          Aujourd'hui
        </button>
        <button
          type="button"
          class="btn"
          [class.btn-primary]="period() === 'week'"
          [class.btn-secondary]="period() !== 'week'"
          (click)="setPeriod('week')"
        >
          Cette semaine
        </button>
        <button
          type="button"
          class="btn"
          [class.btn-primary]="period() === 'month'"
          [class.btn-secondary]="period() !== 'month'"
          (click)="setPeriod('month')"
        >
          Ce mois
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="card stat-card">
        <span class="stat-label">Chiffre d'affaires</span>
        <span class="stat-value">{{ summary()?.revenue | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Bénéfice estimé</span>
        <span class="stat-value">{{ summary()?.estimatedProfit | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Dépenses</span>
        <span class="stat-value">{{ summary()?.expensesTotal | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Créances (impayés)</span>
        <span class="stat-value">{{ summary()?.balanceDue | fcfa }}</span>
      </div>
      <div class="card stat-card">
        <span class="stat-label">Ventes</span>
        <span class="stat-value">{{ summary()?.salesCount ?? 0 }}</span>
      </div>
    </div>

    <div class="card chart-card">
      <h2>Évolution du chiffre d'affaires</h2>
      <app-revenue-chart [points]="revenueSeries()" />
    </div>

    <div class="columns">
      <div class="card">
        <h2>Produits les plus vendus</h2>
        @if (topProducts().length === 0) {
          <div class="empty-state">Aucune vente sur cette période.</div>
        } @else {
          <table class="data-table">
            <thead>
              <tr>
                <th>Produit</th>
                <th>Qté vendue</th>
                <th>CA généré</th>
              </tr>
            </thead>
            <tbody>
              @for (product of topProducts(); track product.productId) {
                <tr>
                  <td>{{ product.name }}</td>
                  <td>{{ product.quantitySold }}</td>
                  <td>{{ product.revenue | fcfa }}</td>
                </tr>
              }
            </tbody>
          </table>
        }
      </div>

      <div class="card">
        <h2>Meilleurs clients</h2>
        @if (topCustomers().length === 0) {
          <div class="empty-state">Aucune vente sur cette période.</div>
        } @else {
          <table class="data-table">
            <thead>
              <tr>
                <th>Client</th>
                <th>Dépensé</th>
                <th>Impayé</th>
              </tr>
            </thead>
            <tbody>
              @for (customer of topCustomers(); track customer.customerId) {
                <tr>
                  <td>{{ customer.name }}</td>
                  <td>{{ customer.totalSpent | fcfa }}</td>
                  <td>
                    @if (Number(customer.balanceDue) > 0) {
                      <span class="badge badge-danger">{{ customer.balanceDue | fcfa }}</span>
                    } @else {
                      <span class="badge badge-success">À jour</span>
                    }
                  </td>
                </tr>
              }
            </tbody>
          </table>
        }
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
              <th>Total</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @for (sale of recentSales(); track sale.id) {
              <tr>
                <td>{{ sale.saleDate | date: 'dd/MM/yyyy HH:mm' }}</td>
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

      .period-switch {
        display: flex;
        gap: 8px;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
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

      .chart-card {
        margin-bottom: 20px;
      }

      .columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
      }

      @media (max-width: 900px) {
        .columns {
          grid-template-columns: 1fr;
        }
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
  private readonly statsService = inject(StatsService);
  private readonly saleService = inject(SaleService);

  protected readonly Number = Number;
  protected readonly period = signal<Period>('month');

  protected readonly summary = signal<StatsSummary | null>(null);
  protected readonly topProducts = signal<TopProduct[]>([]);
  protected readonly topCustomers = signal<TopCustomer[]>([]);
  protected readonly revenueSeries = signal<RevenuePoint[]>([]);
  protected readonly recentSales = signal<Sale[]>([]);

  constructor() {
    this.loadStats();
    this.loadRecentSales();
  }

  setPeriod(period: Period): void {
    this.period.set(period);
    this.loadStats();
  }

  private range(): StatsRange {
    const now = new Date();
    const to = now;
    let from: Date;

    if (this.period() === 'today') {
      from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    } else if (this.period() === 'week') {
      const day = (now.getDay() + 6) % 7; // Monday = 0
      from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day);
    } else {
      from = new Date(now.getFullYear(), now.getMonth(), 1);
    }

    return { from, to };
  }

  private loadStats(): void {
    const range = this.range();

    forkJoin({
      summary: this.statsService.summary(range),
      topProducts: this.statsService.topProducts(range),
      topCustomers: this.statsService.topCustomers(range),
      revenueSeries: this.statsService.revenueSeries(range),
    }).subscribe(({ summary, topProducts, topCustomers, revenueSeries }) => {
      this.summary.set(summary);
      this.topProducts.set(topProducts);
      this.topCustomers.set(topCustomers);
      this.revenueSeries.set(revenueSeries);
    });
  }

  private loadRecentSales(): void {
    this.saleService.list().subscribe((collection) => {
      const sorted = [...collection.member].sort(
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
