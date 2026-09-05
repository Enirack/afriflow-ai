import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CreateExpensePayload, Expense } from '../models/expense.model';
import { HydraCollection } from '../models/hydra-collection.model';

const LD_JSON_HEADERS = { 'Content-Type': 'application/ld+json' };

@Injectable({ providedIn: 'root' })
export class ExpenseService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/expenses`;

  list(): Observable<HydraCollection<Expense>> {
    return this.http.get<HydraCollection<Expense>>(this.baseUrl);
  }

  create(payload: CreateExpensePayload): Observable<Expense> {
    return this.http.post<Expense>(this.baseUrl, payload, { headers: LD_JSON_HEADERS });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/${id}`);
  }
}
