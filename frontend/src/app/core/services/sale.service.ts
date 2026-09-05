import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { HydraCollection } from '../models/hydra-collection.model';
import { CreatePaymentPayload, CreateSalePayload, Payment, Sale } from '../models/sale.model';

const LD_JSON_HEADERS = { 'Content-Type': 'application/ld+json' };

@Injectable({ providedIn: 'root' })
export class SaleService {
  private readonly http = inject(HttpClient);
  private readonly salesUrl = `${environment.apiUrl}/sales`;
  private readonly paymentsUrl = `${environment.apiUrl}/payments`;

  list(): Observable<HydraCollection<Sale>> {
    return this.http.get<HydraCollection<Sale>>(this.salesUrl);
  }

  get(id: number): Observable<Sale> {
    return this.http.get<Sale>(`${this.salesUrl}/${id}`);
  }

  create(payload: CreateSalePayload): Observable<Sale> {
    return this.http.post<Sale>(this.salesUrl, payload, { headers: LD_JSON_HEADERS });
  }

  recordPayment(payload: CreatePaymentPayload): Observable<Payment> {
    return this.http.post<Payment>(this.paymentsUrl, payload, { headers: LD_JSON_HEADERS });
  }
}
