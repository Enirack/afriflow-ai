import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { RevenuePoint, StatsSummary, TopCustomer, TopProduct } from '../models/stats.model';

export interface StatsRange {
  from: Date;
  to: Date;
}

@Injectable({ providedIn: 'root' })
export class StatsService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/stats`;

  summary(range: StatsRange): Observable<StatsSummary> {
    return this.http.get<StatsSummary>(`${this.baseUrl}/summary`, { params: this.toParams(range) });
  }

  topProducts(range: StatsRange, limit = 5): Observable<TopProduct[]> {
    return this.http.get<TopProduct[]>(`${this.baseUrl}/top-products`, {
      params: this.toParams(range).set('limit', limit),
    });
  }

  topCustomers(range: StatsRange, limit = 5): Observable<TopCustomer[]> {
    return this.http.get<TopCustomer[]>(`${this.baseUrl}/top-customers`, {
      params: this.toParams(range).set('limit', limit),
    });
  }

  revenueSeries(range: StatsRange): Observable<RevenuePoint[]> {
    return this.http.get<RevenuePoint[]>(`${this.baseUrl}/revenue-series`, {
      params: this.toParams(range),
    });
  }

  private toParams(range: StatsRange): HttpParams {
    return new HttpParams()
      .set('from', range.from.toISOString())
      .set('to', range.to.toISOString());
  }
}
