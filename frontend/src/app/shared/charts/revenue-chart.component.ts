import { Component, computed, input } from '@angular/core';
import { RevenuePoint } from '../../core/models/stats.model';

const WIDTH = 720;
const HEIGHT = 220;
const PADDING = 28;

@Component({
  selector: 'app-revenue-chart',
  template: `
    @if (points().length === 0) {
      <div class="empty-state">Pas encore de données sur cette période.</div>
    } @else {
      <svg [attr.viewBox]="'0 0 ' + width + ' ' + height" preserveAspectRatio="none" class="chart">
        <line
          [attr.x1]="padding"
          [attr.y1]="height - padding"
          [attr.x2]="width - padding"
          [attr.y2]="height - padding"
          class="axis"
        />
        <polyline [attr.points]="areaPoints()" class="area" />
        <polyline [attr.points]="linePoints()" class="line" />
        @for (dot of dots(); track $index) {
          <circle [attr.cx]="dot.x" [attr.cy]="dot.y" r="3" class="dot" />
        }
      </svg>
      <div class="chart-labels">
        <span>{{ firstLabel() }}</span>
        <span class="max-label">Max : {{ maxLabel() }}</span>
        <span>{{ lastLabel() }}</span>
      </div>
    }
  `,
  styles: [
    `
      :host {
        display: block;
      }

      .chart {
        width: 100%;
        height: 200px;
        display: block;
      }

      .axis {
        stroke: var(--color-border);
        stroke-width: 1;
      }

      .line {
        fill: none;
        stroke: var(--color-primary);
        stroke-width: 2.5;
        stroke-linejoin: round;
        stroke-linecap: round;
      }

      .area {
        fill: rgba(14, 124, 97, 0.1);
        stroke: none;
      }

      .dot {
        fill: var(--color-primary);
      }

      .chart-labels {
        display: flex;
        justify-content: space-between;
        color: var(--color-text-muted);
        font-size: 12px;
        margin-top: 4px;
      }

      .max-label {
        font-weight: 600;
      }
    `,
  ],
})
export class RevenueChartComponent {
  readonly points = input<RevenuePoint[]>([]);
  readonly width = WIDTH;
  readonly height = HEIGHT;
  readonly padding = PADDING;

  private readonly values = computed(() => this.points().map((p) => Number(p.revenue)));
  private readonly maxValue = computed(() => Math.max(1, ...this.values()));

  protected readonly dots = computed(() => {
    const values = this.values();
    const max = this.maxValue();
    const innerWidth = WIDTH - PADDING * 2;
    const innerHeight = HEIGHT - PADDING * 2;
    const step = values.length > 1 ? innerWidth / (values.length - 1) : 0;

    return values.map((value, index) => ({
      x: PADDING + step * index,
      y: PADDING + innerHeight - (value / max) * innerHeight,
    }));
  });

  protected readonly linePoints = computed(() =>
    this.dots()
      .map((d) => `${d.x},${d.y}`)
      .join(' '),
  );

  protected readonly areaPoints = computed(() => {
    const dots = this.dots();
    if (dots.length === 0) {
      return '';
    }
    const baseline = HEIGHT - PADDING;
    const first = `${dots[0].x},${baseline}`;
    const last = `${dots[dots.length - 1].x},${baseline}`;
    return `${first} ${this.linePoints()} ${last}`;
  });

  protected readonly maxLabel = computed(() =>
    new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(this.maxValue()) + ' FCFA',
  );

  protected readonly firstLabel = computed(() => this.points()[0]?.date ?? '');
  protected readonly lastLabel = computed(() => this.points()[this.points().length - 1]?.date ?? '');
}
