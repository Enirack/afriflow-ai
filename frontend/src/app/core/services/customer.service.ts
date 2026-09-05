import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CreateCustomerPayload, Customer } from '../models/customer.model';
import { HydraCollection } from '../models/hydra-collection.model';

const LD_JSON_HEADERS = { 'Content-Type': 'application/ld+json' };

@Injectable({ providedIn: 'root' })
export class CustomerService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/customers`;

  list(): Observable<HydraCollection<Customer>> {
    return this.http.get<HydraCollection<Customer>>(this.baseUrl);
  }

  create(payload: CreateCustomerPayload): Observable<Customer> {
    return this.http.post<Customer>(this.baseUrl, payload, { headers: LD_JSON_HEADERS });
  }

  update(id: number, payload: Partial<CreateCustomerPayload>): Observable<Customer> {
    return this.http.patch<Customer>(`${this.baseUrl}/${id}`, payload, {
      headers: { 'Content-Type': 'application/merge-patch+json' },
    });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/${id}`);
  }
}
