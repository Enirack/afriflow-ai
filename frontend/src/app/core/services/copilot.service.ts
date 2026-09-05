import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

const LD_JSON_HEADERS = { 'Content-Type': 'application/ld+json' };

@Injectable({ providedIn: 'root' })
export class CopilotService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/copilot`;

  ask(question: string): Observable<{ answer: string }> {
    return this.http.post<{ answer: string }>(
      `${this.baseUrl}/ask`,
      { question },
      { headers: LD_JSON_HEADERS },
    );
  }

  analyze(): Observable<{ report: string }> {
    return this.http.post<{ report: string }>(`${this.baseUrl}/analyze`, {}, { headers: LD_JSON_HEADERS });
  }
}
