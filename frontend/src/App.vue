<template>
  <div class="flex flex-col h-full">
    <div class="flex flex-1 justify-center">
      <line-chart :chart-data="datacollection" :height="180" :width="600"></line-chart>
    </div>
    <div class="flex flex-1 gap-4 p-4">
      <div class="flex flex-1 flex-col">
        <h2 class="uppercase text-center text-xs p-2">
          Urinary output in last 24 hours
        </h2>
        <div class="flex flex-1 flex-col gap-4 bg-blue-100 rounded-2xl justify-center items-center p-4 text-center">
          <p class="font-bold text-4xl">
            {{this.allUrine}}
          </p>
          <p class="text-gray-500 text-xs">ml/kg/h</p>
        </div>
      </div>
      <div class="flex flex-1 flex-col">
        <h2 class="uppercase text-center text-xs p-2">
          Current urinary output per hour
        </h2>
        <div class="flex flex-1 flex-col gap-4 bg-blue-100 rounded-2xl justify-center items-center p-4 text-center">
          <p class="font-bold text-4xl">
            10
          </p>
          <p class="text-gray-500 text-xs">ml/kg</p>
        </div>
      </div>
      <div class="flex flex-1 flex-col">
        <h2 class="uppercase text-center text-xs p-2">
          Toe-to-Room difference in temperature
        </h2>
        <div class="flex flex-1 flex-col gap-4 bg-blue-100 rounded-2xl justify-center items-center p-4 text-center">
          <p class="font-bold text-4xl">
            {{this.tempDiff}}
          </p>
          <p class="text-gray-500 text-xs">C</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LineChart from "@/components/LineChart";
import axios from 'axios';

export default {
  name: 'App',
  components: {LineChart},
  data () {
    return {
      allData: {},
      weightData: [],
      weightLabel: [],
      tempDiff: 0,
      allUrine: 0
    }
  },
  mounted() {
    this.getAllData()
    this.interval = setInterval(() => this.getAllData(), 30000);
  },
  computed: {
    datacollection: function () {
      return  {
        labels:  this.weightLabel,
        datasets: [
          {
            label: 'Urine',
            backgroundColor: '#fdc968',
            data: this.weightData
          }
        ]
      }
    }
  },
  methods: {
    getAllData() {
      axios.get(process.env.VUE_APP_API_URL + "/api/all").then(
          (result) => {
            this.allData = result.data.data
            console.log(this.allData)

            this.allUrine = 0
            this.weightData = []
            this.weightLabel = []

            for (let item of this.allData.weights) {
              this.weightData.push(item.last_value + 11)
              let labelForUrine = item.value_updated_at.substring(11,19)
              this.weightLabel.push(labelForUrine)

              this.allUrine +=  item.last_value + 11
            }

            this.allUrine /= 70 * 24
            this.allUrine = Math.round(this.allUrine * 100) / 100

            this.tempDiff = this.allData.actualCentrTemp.last_value - this.allData.actualPerifTemp.last_value
            this.tempDiff = Math.round(this.tempDiff * 100) / 100
          }
      )
    },
  }
}
</script>

<style>
</style>
