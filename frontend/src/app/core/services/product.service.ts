import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { HydraCollection } from '../models/hydra-collection.model';
import { CreateProductPayload, Product } from '../models/product.model';

const LD_JSON_HEADERS = { 'Content-Type': 'application/ld+json' };

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/products`;

  list(): Observable<HydraCollection<Product>> {
    return this.http.get<HydraCollection<Product>>(this.baseUrl);
  }

  create(payload: CreateProductPayload): Observable<Product> {
    return this.http.post<Product>(this.baseUrl, payload, { headers: LD_JSON_HEADERS });
  }

  update(id: number, payload: Partial<CreateProductPayload>): Observable<Product> {
    return this.http.patch<Product>(`${this.baseUrl}/${id}`, payload, {
      headers: { 'Content-Type': 'application/merge-patch+json' },
    });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/${id}`);
  }
}
