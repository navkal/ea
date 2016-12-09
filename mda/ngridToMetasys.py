import csv
from datetime import datetime, date, time, timedelta
import argparse
import pandas as pd

def NGtoMet( ngridfile, metasysfile ):
  df = pd.read_csv( ngridfile, nrows=10)
  df.sort_values( by=['Date'], inplace=True )
  headings = df.columns.values
  print( headings )

  with open( metasysfile, mode='w', newline="", encoding='utf-8' ) as w:
    csvwriter = csv.writer( w )
    csvwriter.writerow( ['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'] )

    sum = {}

    for ngridline in df.itertuples():
      if ( ngridline[1] != "" ) and ( ngridline[2] != "" ) and ( ngridline[4] != "" ):
        colname = ngridline[1] + '.' + ngridline[4]
        sumname = colname + ".sum"
        for index in range( 4, len( ngridline ) - 1 ):
          cell = ngridline[index+1]
          if cell != "":
            timesplit = headings[index].split( ':' )
            datesplit = ngridline[2].split( '/' )
            dt = datetime( int( datesplit[2] ), int( datesplit[0] ), int( datesplit[1] ) )
            if ( timesplit[0] == '24' ):
              dt += timedelta( days=1 )
              timesplit[0] = '00'
            dt += timedelta( hours=int(timesplit[0]), minutes=int(timesplit[1]) )
            timestamp = dt.strftime( '%m/%d/%Y %H:%M' )
            print( index, timestamp, cell )
            csvwriter.writerow( [ timestamp, '', colname, cell ] )

  return





  with open(outputfile, mode='w', newline="", encoding='utf-8') as w:
      csvwriter = csv.writer(w)
      csvwriter.writerow(['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'])
      n = 0
      oneday = timedelta(days=1)
      KWhtotal = 0
      KVAhTotal = 0
      with open('archive/temp.csv',mode='r') as f:
          csvfile = csv.reader(f)
          for row in csvfile:
              if(n == 0):
                  n += 1
                  TimeIndex = row[4:]
                  if '24:00:00' in TimeIndex:
                      for z in range(len(TimeIndex)):
                          if TimeIndex[z] == "24:00:00":
                              TimeIndex[z] = "00:00:00:01"
                  #print(row)
              elif(n > 0):
                  n += 1
                  account = row[0]
                  if row[1] == '':
                      continue
                  datesplit = row[1].split('-')
                  print(int(datesplit[2]),int(datesplit[1]),int(datesplit[0]))
                  d = date(int(datesplit[0]),int(datesplit[1]),int(datesplit[2]))
                  units = row[3]
                  SpotMeasures = row[4:]
                  #print(SpotMeasures)
                  for x in range(len(TimeIndex)):
                      #print(TimeIndex[x])
                      timesplit = TimeIndex[x].split(':')
                      #print(timesplit)
                      t = time(int(timesplit[0]), int(timesplit[1]))
                      if len(timesplit) == 4:
                          d = oneday + d
                      if SpotMeasures[x] == '':
                          continue
                      if units == 'kWh':
                          KWhtotal += float(SpotMeasures[x])
                          dt = datetime.combine(d, t)
                          #print(dt)
                          NamePathRef = account + '.' + units
                          csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef, NamePathRef, SpotMeasures[x]])
                          csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef + '.sum', NamePathRef + '.sum', KWhtotal])
                      elif units == 'kVAh':
                          KVAhTotal += float(SpotMeasures[x])
                          dt = datetime.combine(d, t)
                          #print(dt)
                          NamePathRef = account + '.' + units
                          csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'),NamePathRef,NamePathRef,SpotMeasures[x]])
                          csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'),NamePathRef + '.sum',NamePathRef + '.sum',+ KVAhTotal])
                      elif units == 'Power Factor':
                          dt = datetime.combine(d, t)
                          #print(dt)
                          NamePathRef = account + '.' + units
                          csvwriter.writerow([dt.strftime('%m/%d/%Y %H:%M'), NamePathRef, NamePathRef, SpotMeasures[x]])

              if n > 3889838388:
                  break
              #print(n)

if __name__ == "__main__":
  parser = argparse.ArgumentParser( description='converts NG output files to metasys filetype' )
  parser.add_argument( '-i', dest='input_file',  help='name of input file' )
  parser.add_argument( '-o', dest='output_file', help='name of output file' )
  args = parser.parse_args()
  NGtoMet( args.input_file,args.output_file )

#NGtoMet('input/ngrid_3cad443c_046db54d_hourly.csv','archive/converted_1.csv')
#NGtoMet('12-16 DATA.csv','testfile.csv')
